<?php

namespace Kaliba\Http;

use RuntimeException;
use InvalidArgumentException;
use Kaliba\Http\Contracts\StreamInterface;
use Kaliba\Http\Contracts\UploadFileInterface;
use Kaliba\Filesystem\Exceptions\FileException;
use Kaliba\Filesystem\File;

/**
 * Represents Uploaded Files.
 *
 * It manages and normalizes uploaded files according to the PSR-7 standard.
 *
 */
class UploadFile extends File implements UploadFileInterface
{

    /**
     * Whether the test mode is activated.
     *
     * Local files are used in test mode hence the code should not enforce HTTP uploads.
     *
     * @var bool
     */
    protected $sapi = false;

    /**
     * The original name of the uploaded file.
     *
     * @var string
     */
    protected $originalName;

    /**
     * The mime type provided by the uploader.
     *
     * @var string
     */
    protected $mimeType;

    /**
     * The file size provided by the uploader.
     *
     * @var string
     */
    protected $size;

    /**
     * The UPLOAD_ERR_XXX constant provided by the uploader.
     *
     * @var int
     */
    protected $error;
    
    /**
     * An optional StreamInterface wrapping the file resource.
     *
     * @var StreamInterface
     */
    protected $stream;
    
    /**
     * Indicates if the uploaded file has already been moved.
     *
     * @var bool
     */
    protected $moved = false;
    
    /**
     *  Target path
     * @var string
     */
    protected $savePath;
    
    /**
     * Accepts the information of the uploaded file as provided by the PHP global $_FILES.
     *
     * The file object is only created when the uploaded file is valid (i.e. when the
     * isValid() method returns true). Otherwise the only methods that could be called
     * on an UploadedFile instance are:
     *
     *   * getClientOriginalName,
     *   * getClientMimeType,
     *   * isValid,
     *   * getError.
     *
     * Calling any other method on an non-valid instance will cause an unpredictable result.
     *
     * @param string $path         The full temporary path to the file
     * @param string $originalName The original file name
     * @param string $mimeType     The type of the file as provided by PHP
     * @param int    $size         The file size
     * @param int    $error        The error constant of the upload (one of PHP's UPLOAD_ERR_XXX constants)
     * @param bool   $sapi         Whether the test mode is active
     *
     * @throws FileException         If file_uploads is disabled
     * @throws FileNotFoundException If the file does not exist
     */
    public function __construct($path, $originalName, $mimeType = null, $size = null, $error = null, $sapi = false)
    {
        $this->originalName = $this->getName($originalName);
        $this->mimeType = $mimeType ?: 'application/octet-stream';
        $this->size = $size;
        $this->error = $error ?: UPLOAD_ERR_OK;
        $this->sapi = (bool) $sapi;

        parent::__construct($path, UPLOAD_ERR_OK === $this->error);
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * This method MUST return a StreamInterface instance, representing the
     * uploaded file. The purpose of this method is to allow utilizing native PHP
     * stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a
     * native PHP stream wrapper to work with such functions).
     *
     * If the moveTo() method has been called previously, this method MUST raise
     * an exception.
     *
     * @return Kaliba\Http\Stream Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream()
    {
        if ($this->moved) {
            throw new RuntimeException(sprintf('Uploaded file %1s has already been moved', $this->name));
        }
        if ($this->stream === null) {
            $this->stream = new Stream(fopen($this->getPathname(), 'r'));
        }

        return $this->stream;
    }
   
    /**
     * Retrieve the error associated with the uploaded file.
     *
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method MUST return
     * UPLOAD_ERR_OK.
     *
     * Implementations SHOULD return the value stored in the "error" key of
     * the file in the $_FILES array.
     *
     * @see http://php.net/manual/en/features.file-upload.errors.php
     *
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "name" key of
     * the file in the $_FILES array.
     *
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getFilename()
    {
        return $this->originalName;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * Implementations SHOULD return the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getMimetype()
    {
        return $this->mimeType;
    }
    
    /**
     * Retrieve the file extension sent by the client.
     *
     * @return string|null The file extension sent by the client or null if none
     *     was provided.
     */
    public function getExtension()
    {
        return pathinfo($this->originalName, PATHINFO_EXTENSION);
    }

    /**
     * Retrieve the file size.
     *
     * Implementations SHOULD return the value stored in the "size" key of
     * the file in the $_FILES array if available, as PHP calculates this based
     * on the actual size transmitted.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getFilesize()
    {
        return $this->size;
    }   
    
    /**
     * Gets the save path of the uploaded file
     * The path is set when a file is uploaded;
     * @return string|null
     */
    public function getSavePath()
    {
        return $this->savePath;
    }
    
    /**
     * Returns an informative upload error message.
     *
     * @return string The error message regarding the specified error code
     */
    public function getErrorMessage()
    {
        static $errors = array(
            UPLOAD_ERR_INI_SIZE => 'The file "%s" exceeds your upload_max_filesize ini directive (limit is %d KiB).',
            UPLOAD_ERR_FORM_SIZE => 'The file "%s" exceeds the upload limit defined in your form.',
            UPLOAD_ERR_PARTIAL => 'The file "%s" was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
            UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
            UPLOAD_ERR_EXTENSION => 'File upload was stopped by a PHP extension.',
        );

        $errorCode = $this->error;
        $maxFilesize = $errorCode === UPLOAD_ERR_INI_SIZE ? self::getMaxFilesize() / 1024 : 0;
        $message = isset($errors[$errorCode]) ? $errors[$errorCode] : 'The file "%s" was not uploaded due to an unknown error.';

        return sprintf($message, $this->getFilename(), $maxFilesize);
    }
   
    /**
     * Returns whether the file was uploaded successfully.
     *
     * @return bool True if the file has been uploaded with HTTP and no error occurred.
     */
    public function isValid()
    {
        return  ($this->error == UPLOAD_ERR_OK)&&( is_uploaded_file($this->getPathname()) );
    }    
    
    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method is
     * guaranteed to work in both SAPI and non-SAPI environments.
     * Implementations must determine which environment they are in, and use the
     * appropriate method (move_uploaded_file(), rename(), or a stream
     * operation) to perform the operation.
     *
     * $savePathPath may be an absolute path, or a relative path. If it is a
     * relative path, resolution should be the same as used by PHP's rename()
     * function.
     *
     * The original file or stream MUST be removed on completion.
     *
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     *
     * When used in an SAPI environment where $_FILES is populated, when writing
     * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
     * used to ensure permissions and upload status are verified correctly.
     *
     * If you wish to move to a stream, use getStream(), as SAPI operations
     * cannot guarantee writing to stream destinations.
     *
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     *
     * @param string $savePathPath Path to which to move the uploaded file.
     *
     * @throws InvalidArgumentException if the $path specified is invalid.
     * @throws RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function move($directory, $name = null)
    {
        $filename = empty($name) ? $this->originalName : $name;
        $savePathPath = $this->getTargetFile($directory, $filename);
        $savePathIsStream = strpos($savePathPath, '://') > 0;
        
        if (!is_writable(dirname($savePathPath))) {
            throw new InvalidArgumentException('Upload savePath path is not writable');
        }       
        if ($savePathIsStream) {
            if (!copy($this->getPathname(), $savePathPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->originalName, $savePathPath));
            }
            if (!unlink($this->getPathname())) {
                throw new RuntimeException(sprintf('Error removing uploaded file %1s', $this->originalName));
            }
        } elseif ($this->sapi) {
            if (!$this->isValid()) {
                throw new FileException($this->getErrorMessage());
            }
            if (!move_uploaded_file($this->getPathname(), $savePathPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->originalName, $savePathPath));
            }
        } else {
            if (!rename($this->getPathname(), $savePathPath)) {
                throw new RuntimeException(sprintf('Error moving uploaded file %1s to %2s', $this->originalName, $savePathPath));
            }
        }
        $this->savePath = $savePathPath;
        $this->moved = true;         
    }
    
    /**
     * Checks whether file has been moved to the upload savePath
     * @return boolean
     */
    public function moved()
    {
        return $this->moved;
    }   
    	
    /**
     * Returns the maximum size of an uploaded file as configured in php.ini.
     *
     * @return int The maximum size of an uploaded file in bytes
     */
    public static function getMaxFilesize()
    {
        $iniMax = strtolower(ini_get('upload_max_filesize'));

        if ('' === $iniMax) {
            return PHP_INT_MAX;
        }

        $max = ltrim($iniMax, '+');
        if (0 === strpos($max, '0x')) {
            $max = intval($max, 16);
        } elseif (0 === strpos($max, '0')) {
            $max = intval($max, 8);
        } else {
            $max = (int) $max;
        }

        switch (substr($iniMax, -1)) {
            case 't': $max *= 1024;
            case 'g': $max *= 1024;
            case 'm': $max *= 1024;
            case 'k': $max *= 1024;
        }

        return $max;
    }
    
    /**
     * Parse a non-normalized, i.e. $_FILES superglobal, tree of uploaded file data.
     *
     * @param array $uploadedFiles The non-normalized tree of uploaded file data.
     *
     * @return array A normalized tree of UploadedFile instances.
     */
    public static function createFromGrobal(array $uploadedFiles)
    {
        $parsed = [];
        foreach ($uploadedFiles as $field => $uploadedFile) {
            if (!isset($uploadedFile['error'])) {
                if (is_array($uploadedFile)) {
                    $parsed[$field] = static::createFromGrobal($uploadedFile);
                }
                continue;
            }

            $parsed[$field] = [];
            if (!is_array($uploadedFile['error'])) {
                $parsed[$field] = new static(
                    $uploadedFile['tmp_name'],
                    isset($uploadedFile['name']) ? $uploadedFile['name'] : null,
                    isset($uploadedFile['type']) ? $uploadedFile['type'] : null,
                    isset($uploadedFile['size']) ? $uploadedFile['size'] : null,
                    $uploadedFile['error'],
                    true
                );
            } else {
                $subArray = [];
                foreach ($uploadedFile['error'] as $fileIdx => $error) {
                    // normalise subarray and re-parse to move the input's keyname up a level
                    $subArray[$fileIdx]['name'] = $uploadedFile['name'][$fileIdx];
                    $subArray[$fileIdx]['type'] = $uploadedFile['type'][$fileIdx];
                    $subArray[$fileIdx]['tmp_name'] = $uploadedFile['tmp_name'][$fileIdx];
                    $subArray[$fileIdx]['error'] = $uploadedFile['error'][$fileIdx];
                    $subArray[$fileIdx]['size'] = $uploadedFile['size'][$fileIdx];

                    $parsed[$field] = static::createFromGrobal($subArray);
                }
            }
        }

        return $parsed;
    }

}
