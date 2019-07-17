<?php
namespace Kaliba\Filesystem\Contracts;

interface FileUploaderInterface 
{
    /**
     * Set the path to which the file will be uploaded and saved
     * @param string $savepath
     */
    public function setSavePath(string $savepath);
    
    /**
     * Get the save path where files are uploaded and saved
     * @return string
     */
    public function getSavePath(): string;
    
    /**
     * Set the allowed maximum file size of the upload file 
     * @param int $size
     */
    public function setFileSize(int $size);
    
    /**
     * Get the allowed maximum file size of the upload file
     * @return int
     */
    public function getFileSize(): int;
    
    /**
     * Turn on of off file renaming
     * @param bool $flag
     */
    public function renameFile(bool $flag=true);
    
    /**
     * Set the allowed file mime type
     * @param string $mimetype
     */
    public function setMimeType(string $mimetype);
    
    /**
     * Set multiple allowed file mime types
     * @param array $mimetypes
     */
    public function setMimeTypes(array $mimetypes);
    
    /**
     * Get file upload errors
     * @return array
     */
    public function getErrors(): array;
    
    /**
     * Get the absolute file path of the uploaded file
     * @return string
     */
    public function getFilePath(): string;
    
    /**
     * Do file upload to the server directory
     * @return boolean
     */
    public function upload();
    
    /**
     * Check whether file was uploaded successfully
     * @return bool
     */
    public function isValid(): bool;
}
