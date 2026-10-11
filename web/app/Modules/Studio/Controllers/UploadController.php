<?php

namespace App\Modules\Studio\Controllers;

use App\Libraries\Uploads;

/**
 * Image uploads from the rich-text editor and cover pickers. Returns JSON.
 */
class UploadController extends StudioController
{
    public function image()
    {
        try {
            $path = Uploads::image($this->request->getFile('file'), 'editor', 6144);
        } catch (\RuntimeException $e) {
            return $this->failed($e->getMessage());
        }
        if (! $path) {
            return $this->failed('Choose an image to upload.');
        }

        return $this->done('Uploaded.', ['path' => $path, 'url' => base_url($path)]);
    }
}
