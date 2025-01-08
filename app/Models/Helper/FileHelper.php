<?php

namespace App\Models\Helper;

use Google\Cloud\Storage\StorageClient;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;



class FileHelper
{


    public static function getUploadPath()
    {
        // For cpanel
        // return base_path() . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

        // For artisan / VPS
        return base_path() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
    }


    public static function uploadPath($image, $deleting = false)
    {
        if ($image === Config::get('constants.media.DEFAULT_IMAGE') && $deleting) {
            $image = time() . '-' . mt_rand(1, 9);
        }

        return self::getUploadPath() . $image;
    }



    public static function imgSrcUrl()
    {
        if (config('env.media.STORAGE') == config('env.media.LOCAL')) {
            return config('env.url.APP_URL') . '/uploads/';

        } else if (config('env.media.STORAGE') == config('env.media.GCS')) {
            return config('env.media.CDN_URL') . 'uploads/';

        } else if (config('env.media.STORAGE') == config('env.media.URL')) {
            return config('env.media.CDN_URL');
        }
    }


    public static function imageLink($image)
    {
        if ($image == 'null' || $image == '' || is_null($image)) {
            $image = Config::get('constants.media.DEFAULT_IMAGE');
        }

        return self::imgSrcUrl() . $image;
    }

    public static function imageFullUrl($image)
    {
        if ($image == 'null' || $image == '' || is_null($image)) {
            $image = Config::get('constants.media.DEFAULT_IMAGE');
        }

        if (config('env.media.STORAGE') == config('env.media.LOCAL')) {
            return env('APP_URL', config('env.url.APP_URL')) . '/uploads/' . $image;

        } else if (config('env.media.STORAGE') == config('env.media.GCS')) {
            return config('env.media.CDN_URL') . 'uploads/' . $image;
        }


        return false;
    }

    public static function imageToBase64($image, $default = true)
    {

        try {

            if ($default && ($image == 'null' || $image == '' || is_null($image))) {
                $image = Config::get('constants.media.DEFAULT_IMAGE');
            }

            $content = '';

            if ($image) {

                if (config('env.media.STORAGE') == config('env.media.LOCAL')) {
                    $path = Storage::disk('public')->getAdapter()->applyPathPrefix($image);

                    if (file_exists($path)) {
                        $content = base64_encode(file_get_contents($path));
                    }


                } else if (config('env.media.STORAGE') == config('env.media.GCS')) {
                    $path = config('env.media.CDN_URL') . 'uploads/' . $image;

                    $content = base64_encode(file_get_contents($path));
                }
            }

            return $content;



        } catch (\Exception $ex) {
            return response()->json(Validation::error(null, $ex->getMessage()));
        }


    }


    public static function deleteFile($image)
    {
        try {

            if (config('env.media.STORAGE') == config('env.media.LOCAL')) {
                return self::deleteFileLocal($image);

            } else if (config('env.media.STORAGE') == config('env.media.GCS')) {
                return self::deleteFileGcs($image);
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function readAllFileGcs()
    {
        try {

            $storage = new StorageClient([
                'keyFilePath' => base_path() . DIRECTORY_SEPARATOR . config('googlecloud.gc_key_file'),
            ]);


            $storageBucketName = config('googlecloud.storage_bucket');
            $bucket = $storage->bucket($storageBucketName);

            $images = [];

            foreach ($bucket->objects() as $object) {
                if(Utils::startsWith($object->name(), 'uploads/')){
                    if($object->name() !== 'uploads/'){
                        array_push($images, str_replace('uploads/', '', $object->name()));
                    }
                }
            }
            return $images;

        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return true;
    }

    public static function deleteFileGcs($image)
    {
        try {

            $storage = new StorageClient([
                'keyFilePath' => base_path() . DIRECTORY_SEPARATOR . config('googlecloud.gc_key_file'),
            ]);


            $storageBucketName = config('googlecloud.storage_bucket');
            $bucket = $storage->bucket($storageBucketName);
            $object = $bucket->object(config('googlecloud.path_prefix') . $image);


            if ($object->exists()) {

                $object->delete();
                $thumbObject = $bucket->object(config('googlecloud.path_prefix') . config('env.media.THUMB_PREFIX') . $image);

                if ($thumbObject->exists()) {
                    $thumbObject->delete();
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }

        return true;
    }

    public static function deleteFileLocal($image)
    {
        try {

            $file_path = $image ? FileHelper::uploadPath($image, true) : null;

            if (file_exists($file_path)) {
                unlink($file_path);

                $thumb_file_path = $image ?
                    FileHelper::uploadPath(config('env.media.THUMB_PREFIX') . $image, true) : null;

                if (file_exists($thumb_file_path)) {
                    unlink($thumb_file_path);
                }
            }
            return true;


        } catch (\Exception $e) {
            throw $e;
        }

    }

    public static function uploadImage($file, $prefix, $thumb = true, $original_name = false)
    {
        try {

            if (config('env.media.STORAGE') == config('env.media.LOCAL')) {
                return self::uploadToLocal($file, $prefix, $thumb, $original_name);

            } else if (config('env.media.STORAGE') == config('env.media.GCS')) {
                return self::uploadToGcs($file, $prefix, $thumb);

            } else if (config('env.media.STORAGE') == config('env.media.URL')) {
                $data['name'] = $file;

                return $data;
            }

            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function uploadToGcs($file, $prefix, $thumb = true)
    {

        try {

            $storage = new StorageClient([
                'keyFilePath' => base_path() . DIRECTORY_SEPARATOR . config('googlecloud.gc_key_file'),
            ]);

            $storageBucketName = config('googlecloud.storage_bucket');
            $bucket = $storage->bucket($storageBucketName);


            $image_path = $file->getRealPath();

            $extension = $file->getClientOriginalExtension();
            $filename = $prefix . '-' . time() . '-' . mt_rand(1, 9) . '.' . $extension;

            $fileSource = fopen($image_path, 'r');
            $googleCloudStoragePath = config('googlecloud.path_prefix') . $filename;


            /* Upload a file to the bucket.
            Using Predefined ACLs to manage object permissions, you may
            upload a file and give read access to anyone with the URL.*/
            $bucket->upload($fileSource, [
                // 'predefinedAcl' => 'publicRead',
                'name' => $googleCloudStoragePath
            ]);

            if ($thumb) {
                $thumbImg = Image::make($file)
                    ->resize(320, 320, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                $googleCloudStorageThumbPath = config('googlecloud.path_prefix') . config('env.media.THUMB_PREFIX') . $filename;

                $bucket->upload($thumbImg->stream(), [
                    'name' => $googleCloudStorageThumbPath
                ]);
            }


            $data['name'] = $filename;

            return $data;


        } catch (\Exception $e) {
            throw $e;
        }




    }

    public static function uploadToLocal($file, $prefix, $thumb = true, $original_name)
    {
        try {

            $extension = $file->getClientOriginalExtension();

            if ($original_name) {
                $filename = $prefix . '.' . $extension;
            }else{
                $filename = $prefix . '-' . time() . '-' . mt_rand(1, 9) . '.' . $extension;
            }
            \Log::info('Request Data:', ['file_name' => $original_name]);
            if ($thumb) {
                self::generateThumbLocal($file, $filename);
            }

            Storage::disk('public')->put($filename, File::get($file));

            $data['name'] = $filename;

            return $data;
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public static function generateThumbLocal($file, $filename)
    {
        try{
            return Image::make($file)
                ->resize(320, 320, function ($constraint) {
                    $constraint->aspectRatio();
                })
                ->save(Storage::disk('public')
                        ->getAdapter()
                        ->getPathPrefix() . DIRECTORY_SEPARATOR . config('env.media.THUMB_PREFIX') . $filename, 80);
        } catch (\Exception $ex) {
            throw $ex;
        }

    }

    public static function searchImageInStorage($imageName)
    {
        $directory = public_path('uploads');
        $searchPattern = $directory . '/' . $imageName . '.*'; // Match any extension

        $foundFiles = glob($searchPattern);

        if (!empty($foundFiles)) {
            // Return the first matching image as a relative path
            return str_replace(public_path(), '', $foundFiles[0]); // Relative path
        }

        return null; // Image not found
    }
}
