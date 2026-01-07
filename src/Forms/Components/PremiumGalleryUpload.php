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

                // Extensive Path Search Strategy
                $filename = $tempPath;
                // $tempPath might be a path like 'livewire-tmp/xyz.jpg' or just 'xyz.jpg'
                // We'll extract just the basename to be safe for our manual checks
                $basename = basename($tempPath);

                $candidates = [
                    // 1. As reported by the disk (Standard)
                    \Storage::disk($diskName)->path($tempPath),

                    // 2. Prefixed on the disk (If input was just filename)
                    \Storage::disk($diskName)->path((config('livewire.temporary_file_upload.directory') ?: 'livewire-tmp') . '/' . $basename),

                    // 3. Fallback: storage/app/livewire-tmp (Legacy/Standard without 'private')
                    storage_path('app/livewire-tmp/' . $basename),

                    // 4. Fallback: storage/app/private/livewire-tmp (Explicit Private)
                    storage_path('app/private/livewire-tmp/' . $basename),

                    // 5. Fallback: storage/app/public/livewire-tmp (Public Disk Temp)
                    storage_path('app/public/livewire-tmp/' . $basename),
                ];

                $finalPath = null;
                foreach ($candidates as $candidate) {
                    if (file_exists($candidate)) {
                        $finalPath = $candidate;
                        break;
                    }
                }

                if (!$finalPath) {
                    // Debug: Show why we failed
                    dd("DEBUG: File Not Found (Extensive Search)", [
                        'candidates_checked' => $candidates,
                        'livewire_config_disk' => config('livewire.temporary_file_upload.disk'),
                        'livewire_config_dir' => config('livewire.temporary_file_upload.directory'),
                        'filesystem_default' => config('filesystems.default'),
                        'storage_path' => storage_path(),
                    ]);
                }

                try {
                    $record->addMedia($finalPath)
                        ->toMediaCollection($collection);
                } catch (\Throwable $e) {
                    // Start a new line log to avoid truncation
                    dd("DEBUG: Error adding media", $e->getMessage(), ['final_path' => $finalPath]);
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
