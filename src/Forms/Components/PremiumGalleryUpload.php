<?php

namespace KreativosPro\PremiumGallery\Forms\Components;

use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;

class PremiumGalleryUpload extends FileUpload
{
    // Updated view path for plugin
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

        // Configure as a generic uploader
        $this->disk('public');
        $this->directory('temp-gallery-uploads');
        $this->multiple();
        $this->image();

        // Manual handling of persistence
        $this->dehydrated(false);

        $this->saveRelationshipsUsing(function (PremiumGalleryUpload $component, Model $record, $state) {
            if (empty($state))
                return;

            $collection = $component->getCollectionName();

            foreach ($state as $tempPath) {
                if (\Storage::disk('public')->exists($tempPath)) {
                    $record->addMediaFromDisk($tempPath, 'public')
                        ->toMediaCollection($collection);
                }
            }
        });

        $this->registerActions([
            Action::make('deleteMedia')
                ->action(function ($component, $arguments) {
                    $mediaId = $arguments['mediaId'] ?? null;
                    if ($mediaId) {
                        \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId)?->delete();
                    }
                }),
            Action::make('setPrimary')
                ->action(function ($component, $arguments) {
                    $mediaId = $arguments['mediaId'] ?? null;
                    if ($mediaId) {
                        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find($mediaId);
                        if ($media) {
                            \Spatie\MediaLibrary\MediaCollections\Models\Media::where('model_type', $media->model_type)
                                ->where('model_id', $media->model_id)
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
