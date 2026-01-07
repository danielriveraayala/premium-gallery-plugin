<?php

namespace KreativosPro\PremiumGallery\Forms\Components;

use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Actions\Action;

class PremiumGalleryUpload extends SpatieMediaLibraryFileUpload
{
    protected string $view = 'premium-gallery::premium-gallery-upload';

    protected function setUp(): void
    {
        parent::setUp();

        // Default configuration
        $this->disk('public');
        $this->image();
        $this->multiple();
        $this->imageEditor();
        $this->hiddenLabel();

        // Register actions for the custom view
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
                            // Reset others in the same collection
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

        // Use the method from Spatie component to get collection name
        $collectionName = $this->getCollectionName();
        $media = $record->getMedia($collectionName);

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
