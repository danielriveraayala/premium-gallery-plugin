<?php

namespace Inmoflow\PremiumGallery\Http\Controllers;

use Illuminate\Routing\Controller;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaController extends Controller
{
    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        $media->delete();
        return response()->json(['success' => true]);
    }

    public function setPrimary($id)
    {
        $media = Media::findOrFail($id);

        Media::where('model_type', $media->model_type)
            ->where('model_id', $media->model_id)
            ->where('collection_name', $media->collection_name)
            ->get()
            ->each(function ($m) {
                $m->setCustomProperty('is_primary', false);
                $m->save();
            });

        $media->setCustomProperty('is_primary', true);
        $media->save();

        return response()->json(['success' => true]);
    }
}
