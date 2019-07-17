<?php
namespace Kaliba\Filesystem;
use Kaliba\Filesystem\Contracts\FileUploaderInterface;
use Kaliba\Http\UploadFile;
use Kaliba\Support\Random;

class FileUploader implements FileUploaderInterface
{
    const FILE_SIZE_KB = 1024;
    const FILE_SIZE_MB = 1048576;
    const FILE_SIZE_GB = 1073741824;
    
    /**
     *
     * @var UploadFile
     */
    private $uploadFile;
    
    /**
     *
     * @var string
     */
    private $savePath;
    
    /**
     *
     * @var int
     */
    private $filesize = 5;
    
    /**
     *
     * @var string
     */
    private $filePath;
    
    /**
     *
     * @var array
     */
    private $mimetypes = array();

    /**
     *
     * @var bool
     */
    private $renameFile =  false;
    
    /**
     *
     * @var bool
     */
    private $isValid = false;
    
    /**
     *
     * @var array
     */
    private $errors = array();
    
    /**
     * Constructor
     * @param UploadFile $uploadFile
     */
    public function __construct(UploadFile $uploadFile) 
    {
        $this->uploadFile = $uploadFile;
    }
    
    /**
     * Set the path to which the file will be uploaded and saved
     * @param string $savepath
     */
    public function setSavePath(string $savepath)
    {
        $this->savePath = $savepath;
    }
    
    /**
     * Get the save path where files are uploaded and saved
     * @return string
     */
    public function getSavePath(): string
    {
        return $this->savePath;
    }
    
    /**
     * Set the allowed maximum file size of the upload file 
     * @param int $size
     */
    public function setFileSize(int $size)
    {
        $this->filesize = $size;
    }
    
    /**
     * Get the allowed maximum file size of the upload file
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->filesize;
    }
    
    /**
     * Turn on of off file renaming
     * @param bool $flag
     */
    public function renameFile(bool $flag)
    {
        $this->renameFile = $flag;
    }
    
    /**
     * Set the allowed file mime type
     * @param string $mimetype
     */
    public function setMimeType(string $mimetype)
    {
        if(!in_array($mimetype, $this->mimetypes)){
            $this->mimetypes[] = $mimetype;
        }
    }
    
    /**
     * Set multiple allowed file mime types
     * @param array $mimetypes
     */
    public function setMimeTypes(array $mimetypes)
    {
        foreach ($mimetypes as $mimetype) {
            $this->setMimeType($mimetype);
        }
    }
    
    /**
     * Get file upload errors
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get the absolute file path of the uploaded file
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }
    
    /**
     * Do file upload to the server directory
     * @return boolean
     */
    public function upload()
    {  
        $maxFileSize = $this->filesize * FILE_SIZE_MB;
        $mimeType = $this->uploadFile->getMimetype();
        $extension = $this->uploadFile->getExtension();
        $fileSize = $this->uploadFile->getSize();
        
        if(!$this->uploadFile->isValid()){
            $this->errors[] = 'Invalid file';
            return false;
        }
        if($fileSize > $maxFileSize){
            $this->errors[] = 'File should be less than '.$this->filesize.'MB';
            return false;
        }
        if($this->uploadFile->isExecutable() || $this->uploadFile->isDir() || !$this->uploadFile->isReadable()){
            $this->errors[] = 'Invalid file';
            return false;
        }
        if(!in_array($mimeType, $this->mimetypes)){
            $this->errors[] = 'Invalid file type';
            return false;
        }
        if($this->renameFile){
            $fileName = Random::random(8).time().".{$extension}";
        }else{
            $fileName = $this->uploadFile->getFilename();
        }
        if(!file_exists($this->savePath) || !is_dir($this->savePath)){
            @mkdir($this->savePath);
        }
        $this->uploadFile->move($this->savePath, $fileName);
        if($this->uploadFile->moved()){
            $this->isValid = true;
            $this->filePath = $this->uploadFile->getSavePath();
        }
    }
    
    /**
     * Check whether file was uploaded successfully
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }
}
