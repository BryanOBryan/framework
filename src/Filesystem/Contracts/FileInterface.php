<?php

namespace Kaliba\Filesystem\Contracts;

interface FileInterface 
{
       
    /**
     * Returns the extension based on the mime type.
     *
     * If the mime type is unknown, returns null.
     *
     * This method uses the mime type as guessed by getMimeType()
     * to guess the file extension.
     *
     * @return string|null The guessed extension or null if it cannot be guessed
     *
     * @see ExtensionGuesser
     * @see getMimeType()
     */
    public function guessExtension();
      
    /**
     * Returns the mime type of the file.
     *
     * The mime type is guessed using a MimeTypeGuesser instance, which uses finfo(),
     * mime_content_type() and the system binary "file" (in this order), depending on
     * which of those are available.
     *
     * @return string|null The guessed mime type (i.e. "application/pdf")
     *
     * @see MimeTypeGuesser
     */
    public function getMimeType();
        
    /**
     * Moves the file to a new location.
     *
     * @param string $directory The destination folder
     * @param string $name      The new file name
     *
     * @return File A File object representing the new file
     *
     * @throws FileException if the target file could not be created
     */
    public function move($directory, $name = null);
    
    /**
     * Change file write mode
     * @param int $mode 
     * @return boolean
     */
    public function changeMode($mode);
    
    /**
     * copies a file to another destination in the file system
     * @param string $destination a path to copy the file to
     * @return boolean
     */
    public function copyTo($destination);
    
    /**
     * deletes a file from the file system
     * @return boolean
     */
    public function delete();
    
    /**
     * renames a file to another name
     * @param string $newname the new name of the renamed file
     * @return boolean
     */
    public function renameTo($newname);
    
    /**
     * checks if a file exists
     * @return boolean
     */
    public function exists();
}
