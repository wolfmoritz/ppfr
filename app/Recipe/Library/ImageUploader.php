<?php
/**
 * Image Uploader Class
 *
 */
namespace Recipe\Library;

class ImageUploader
{
    // The record id for the image directory path
    protected $recordId;

    // Path to root directory of original image uploads
    protected $originalImageFilePathRoot;

    // Path to custom directory
    protected $originalImageFilePath;

    // Final subdirectory for images
    protected $endDirectory = 'files/';

    // Filename to save as
    public $imageFileName;

    // Allowed file types
    protected $allowedMimeTypes = [];

    // Max upload file size
    protected $maxUploadFileSize;

    // Storage delegate for codeguy/upload
    protected $uploadStorage;

    // Logging
    private $log;

    // Messages
    protected $messages = [];

    /**
     * Constructor
     */
    public function __construct(array $config, $log)
    {
        $this->originalImageFilePathRoot = $config['file.path'];
        $this->allowedMimeTypes = $config['file.mimetypes'];
        $this->maxUploadFileSize = $config['file.upload.max.size'];
        $this->log = $log;
    }

    /**
     * Initialize
     *
     * Initial class
     * @param int, the record ID, used to determine subdirectory
     */
    public function initialize($recordId)
    {
        if (!is_numeric($recordId)) {
            $this->log->error('Non-numeric record ID supplied to ImageHandler');
            throw new \Exception('Non-numeric record ID supplied to ImageHandler');
        }

        $this->recordId = $recordId;

        // Make the upload image directory
        $this->makeImagePath();

        // Get file storage delegate
        $this->uploadStorage = new \Upload\Storage\FileSystem($this->originalImageFilePath);
    }

    /**
     * Create Directory Path
     *
     * Defines the custom directory path based on the record ID
     */
    protected function makeImagePath()
    {
        // Create image path, nesting folders by splitting the record ID
        $path = $this->originalImageFilePathRoot . chunk_split($this->recordId, 3, '/') . $this->endDirectory;

        // Create the path if the directory does not exist
        if (!is_dir($path)) {
            try {
                mkdir($path, 0775, true);
            } catch (\Exception $e) {
                $this->log->error('Failed to create image directory path: ' . $path);
                throw new \Exception('Failed to create image directory path');
            }
        }

        $this->originalImageFilePath = $path;
    }

    /**
     * Upload Images
     *
     * Upload specific image from $_FILES array
     * @param string, array key for image
     * @return boolean, true on success or false on failure
     */
    public function upload($imageKeyName)
    {
        // Prepare to upload
        $file = new \Upload\File($imageKeyName, $this->uploadStorage);

        // Set new file name
        $file->setName(uniqid());
        $this->imageFileName = $file->getNameWithExtension();

        // Validate upload
        $file->addValidations([
            // MimeType List => http://www.webmaster-toolkit.com/mime-types.shtml
            new \Upload\Validation\Mimetype($this->allowedMimeTypes),

            // Ensure file is no larger than allowed size
            new \Upload\Validation\Size($this->maxUploadFileSize),
        ]);

        // Try to upload file
        try {
            $file->upload();
        } catch (\Exception $e) {
            // Log errors
            $this->messages = $file->getErrors();
            $this->log->error('File upload errors: ' . print_r($this->messages, true));
            unset($file);

            return false;
        }

        // Reset
        unset($file);
        return true;
    }

    /**
     * Get Error Messages
     *
     * Returns array of error messages
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
