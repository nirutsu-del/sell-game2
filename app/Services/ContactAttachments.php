<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class ContactAttachments
{
    public static function rules(): array
    {
        return ['attachments'=>'nullable|array|max:3','attachments.*'=>'required|file|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:max_width=6000,max_height=6000'];
    }

    // Store once outside transaction retries; remove files if any database write fails.
    public static function withUploads(array $files, callable $save): mixed
    {
        $uploads = [];
        try {
            foreach ($files as $file) {
                $path = $file->store('contact-attachments','local');
                if (!$path) throw new \RuntimeException('Unable to store contact attachment');
                $uploads[] = ['path'=>$path,'mime'=>$file->getMimeType()];
            }
            return $save($uploads);
        } catch (\Throwable $e) {
            foreach ($uploads as $upload) Storage::disk('local')->delete($upload['path']);
            throw $e;
        }
    }
}
