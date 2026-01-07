<?php

namespace KreativosPro\PremiumGallery\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;

class PremiumGalleryUpload extends FileUpload
{
    protected string $view = 'premium-gallery::premium-gallery-upload';

    protected string|null $collectionName = null;

    public function collection(string $collectionName): static
    {
        $this->collectionName = $collectionName;
        return $this;
    }

    public function getCollectionName(): string
    {
        return $this->collectionName ?? $this->getName();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Standard Filament FileUpload configuration
        $this->disk('public'); // Destination disk for final files (optional, SML handles this usually)
        $this->image();
        $this->multiple();
        $this->imageEditor();
        $this->hiddenLabel();

        // IMPORTANT: We handle persistence manually to bridge the custom view + SML
        $this->dehydrated(false);

        $this->saveRelationshipsUsing(function (PremiumGalleryUpload $component, Model $record, $state) {
            if (empty($state))
                return;

            $collection = $component->getCollectionName();

            // Filament stores temp files in the configured temp disk (usually 'local' or 'public')
            // valid for default setup:
            foreach ($state as $tempPath) {
                // Determine the disk Livewire is using for temps
                $diskName = config('livewire.temporary_file_upload.disk') ?: 'local';

                // IGNORE existing media (UUIDs)
                if (\Spatie\MediaLibrary\MediaCollections\Models\Media::where('uuid', $tempPath)->exists()) {
                    continue;
                }

                // Construct absolute paths to check
                $rootPath = \Storage::disk($diskName)->path($tempPath);
                $tempDir = config('livewire.temporary_file_upload.directory') ?: 'livewire-tmp';
                $prefixedPath = \Storage::disk($diskName)->path($tempDir . '/' . $tempPath);

                $finalPath = null;

                if (file_exists($rootPath)) {
                    $finalPath = $rootPath;
                } elseif (file_exists($prefixedPath)) {
                    $finalPath = $prefixedPath;
                } else {
                    // Debugging: If file not found, interrupt to show paths checked
                    dd("DEBUG: File not found (Absolute Check)", [
                        'checked_root' => $rootPath,
                        'checked_prefixed' => $prefixedPath,
                        'disk_used' => $diskName
                    ]);
                }

                try {
                    $record->addMedia($finalPath)
                        ->toMediaCollection($collection);
                } catch (\Throwable $e) {
                    dd("DEBUG: Error adding media", $e->getMessage(), $finalPath);
                }
            }
        });

        $this->registerActions([
            Action::make('deleteMedia')
                ->action(function ($component, $arguments) {
                    $mediaId = $arguments['mediaId'] ?? null;
                    if ($mediaId) {
                        $component->getRecord()->media()->find($mediaId)?->delete();
                    }
                }),
            Action::make('setPrimary')
                ->action(function ($component, $arguments) {
                    $mediaId = $arguments['mediaId'] ?? null;
                    if ($mediaId) {
                        $record = $component->getRecord();
                        $media = $record->media()->find($mediaId);

                        if ($media) {
                            $record->media()
                                ->where('collection_name', $media->collection_name)
                                ->each(function ($m) {
                                    $m->setCustomProperty('is_primary', false);
                                    $m->save();
                                });

                            $media->setCustomProperty('is_primary', true);
                            $media->save();
                        }
                    }
                }),
        ]);
    }

    public function getExistingMedia(): array
    {
        $record = $this->getRecord();

        if (!$record) {
            return [];
        }

        $media = $record->getMedia($this->getCollectionName());

        return $media->map(function ($item) {
            return [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'name' => $item->file_name,
                'size' => $item->human_readable_size,
                'url' => parse_url($item->getUrl(), PHP_URL_PATH),
                'thumbnail' => $item->hasGeneratedConversion('thumb')
                    ? parse_url($item->getUrl('thumb'), PHP_URL_PATH)
                    : parse_url($item->getUrl(), PHP_URL_PATH),
                'isPrimary' => $item->getCustomProperty('is_primary', false),
                'order' => $item->order_column ?? 0,
            ];
        })->values()->toArray();
    }
}
