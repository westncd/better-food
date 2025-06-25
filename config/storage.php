<?php
require_once 'vendor/autoload.php';
use Google\Cloud\Storage\StorageClient;

// Trong terminal của thư mục 
// composer init  
// composer require google/cloud-storage

class CloudStorage {
    private $storage;
    private $bucket;

    public function __construct() {
        $this->storage = new StorageClient([
            'keyFilePath' => __DIR__ . '/../assets/js/service-account-key.json'
        ]);
        $this->bucket = $this->storage->bucket('foodstore-images');
    }

    public function uploadFile($filePath, $destinationPath) {
        $file = fopen($filePath, 'r');
        if ($file === false) {
            throw new Exception("Unable to open file: " . $filePath);
        }
        $object = $this->bucket->upload($file, [
            'name' => $destinationPath
        ]);

        // Tạo Signed URL với thời gian hết hạn (7 ngày)
        $signedUrl = $object->signedUrl(new DateTime('+7 days'));
        return $signedUrl;
    }

    public function deleteFile($filePath) {
        if (empty($filePath)) {
            return false; 
        }
        $object = $this->bucket->object($filePath);
        if ($object->exists()) {
            $object->delete();
            return true;
        }
        return false;
    }
}