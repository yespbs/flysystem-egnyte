<?php

namespace Yespbs\FlysystemEgnyte;
use LogicException;

use \Yespbs\Egnyte\Model\File as FileClient;

use League\Flysystem\Config;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;

/**
 * @todo
 */ 
class EgnyteAdapter extends AbstractAdapter
{
    use NotSupportingVisibilityTrait;
    
    protected $fileClient;
    
    /**
     * construct
     */ 
    public function __construct(FileClient $fileClient, string $prefix = '')
    {
        $this->fileClient = $fileClient;
        $this->setPathPrefix($prefix);
    }

    /**
     * {@inheritdoc}
     * 
     * @see https://developers.egnyte.com/docs/read/File_System_Management_API_Documentation#Upload-a-File
     */
    public function write($path, $contents, Config $config)
    {
        return $this->upload($path, $contents);
    }

    /**
     * {@inheritdoc}
     * 
     * @https://developers.egnyte.com/docs/read/File_System_Management_API_Documentation#Chunked-Upload
     */
    public function writeStream($path, $resource, Config $config)
    {
        return $this->uploadChunked($path, $resource);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        return $this->upload($path, $contents);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->uploadChunked($path, $resource);
    }
    
    /**
     * {@inheritdoc}
     * 
     * @see https://developers.egnyte.com/docs/read/File_System_Management_API_Documentation#Move-File-or-Folder
     */
    public function rename($path, $newPath): bool
    {
        $path = $this->applyPathPrefix($path);
        $newPath = $this->applyPathPrefix($newPath);

        try {
            $this->fileClient->move($path, $newPath);
        } catch (BadRequest $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     * 
     * @see https://developers.egnyte.com/docs/read/File_System_Management_API_Documentation#Copy-File-or-Folder
     */
    public function copy($path, $newpath): bool
    {
        $path = $this->applyPathPrefix($path);
        $newpath = $this->applyPathPrefix($newpath);

        try {
            $this->fileClient->copy($path, $newpath);
        } catch (BadRequest $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     * 
     * @see https://developers.egnyte.com/docs/read/File_System_Management_API_Documentation#Delete-a-File-or-Folder
     */
    public function delete($path): bool
    {
        $location = $this->applyPathPrefix($path);
        try {
            $this->fileClient->delete($location);
        } catch (BadRequest $e) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname): bool
    {
        return $this->delete($dirname);
    }

    /**
     * {@inheritdoc}
     * 
     * @see https://developers.egnyte.com/docs/read/File_System_Management_API_Documentation#Create-a-Folder
     */
    public function createDir($dirname, Config $config)
    {
        $path = $this->applyPathPrefix($dirname);
        try {
            $object = $this->fileClient->createFolder($path);
        } catch (BadRequest $e) {
            return false;
        }
        return $this->normalizeResponse($object, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        if (! $object = $this->readStream($path)) {
            return false;
        }
        $object['contents'] = stream_get_contents($object['stream']);
        fclose($object['stream']);
        unset($object['stream']);
        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $path = $this->applyPathPrefix($path);
        try {
            $stream = $this->fileClient->download($path);
        } catch (BadRequest $e) {
            return false;
        }
        return compact('stream');
    }

    /**
     * {@inheritdoc}
     * 
     * @todo check return
     */
    public function listContents($directory = '', $recursive = false): array
    {
        $path = $this->applyPathPrefix($directory);
        $result = $this->fileClient->listFolder($path, $recursive);
        if ( (int)$result['total_count']) == 0) {
            return [];
        }

        $folders = array_map(function ($entry) {
            $path = $this->removePathPrefix($entry['path']);
            return $this->normalizeResponse($entry, $path);

        }, $result['folders']);

        $files = array_map(function ($entry) {
            $path = $this->removePathPrefix($entry['path']);
            return $this->normalizeResponse($entry, $path);

        }, $result['files']);

        return array_merge($folders, $files);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        $path = $this->applyPathPrefix($path);
        try {
            $object = $this->fileClient->getMetadata($path);
        } catch (BadRequest $e) {
            return false;
        }
        return $this->normalizeResponse($object, $path);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function applyPathPrefix($path): string
    {
        $path = parent::applyPathPrefix($path);
        return '/'.trim($path, '/');
    }

    public function getClient(): fileClient
    {
        return $this->fileClient;
    }

    /**
     * @param string $path
     * @param resource|string $contents
     * @param string $mode
     *
     * @return array|false file metadata
     */
    protected function upload(string $path, $contents)
    {
        $path = $this->applyPathPrefix($path);
        try {
            $object = $this->fileClient->upload($path, $contents);
        } catch (BadRequest $e) {
            return false;
        }

        return $this->normalizeResponse($object, $path);
    }

    /**
     * @param string $path
     * @param resource|string $contents
     * @param string $mode
     *
     * @return array|false file metadata
     */
    protected function uploadChunked(string $path, $contents)
    {
        $path = $this->applyPathPrefix($path);
        try {
            $object = $this->fileClient->uploadChunked($path, $contents);
        } catch (BadRequest $e) {
            return false;
        }

        return $this->normalizeResponse($object, $path);
    }

    protected function normalizeResponse($response, $path=''): array
    {
        
        if( is_object($response) && isset($response->body) ){
            $response = (array)$response->body;
        }

        if( isset($response['path']) ){
            $normalizedPath = ltrim($this->removePathPrefix($response['path']), '/');
        }else{
            $path = ltrim($this->removePathPrefix($path), '/');
        }

        $normalizedResponse = ['path' => $normalizedPath];

        if (isset($response['lastModified'])) {
            $normalizedResponse['timestamp'] = strtotime($response['lastModified']);
        }

        if (isset($response['size'])) {
            $normalizedResponse['size'] = $response['size'];
            $normalizedResponse['bytes'] = $response['size'];
        }

        $type = ((int)$response['is_folder'] == 1 ? 'dir' : 'file');
        $normalizedResponse['type'] = $type;

        return $normalizedResponse;
    }
}