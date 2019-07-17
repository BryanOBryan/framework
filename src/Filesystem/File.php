<?php

namespace Kaliba\Filesystem;
use Kaliba\Filesystem\Contracts\FileInterface;
use Kaliba\Filesystem\Exceptions\FileException;
use Kaliba\Filesystem\Exceptions\FileNotFoundException;
use Kaliba\Filesystem\MimeType\MimeTypeGuesser;
use Kaliba\Filesystem\MimeType\ExtensionGuesser;
use SplFileInfo;

/**
 * A file in the file system.
 *
 */
class File extends SplFileInfo implements FileInterface
{

    /**
     * Constructs a new file from the given path.
     *
     * @param string $path      The path to the file
     * @param bool   $checkPath Whether to check the path or not
     *
     * @throws Exception If the given path is not a file
     */
    public function __construct($path, $checkPath = true)
    {
        if ($checkPath && !is_file($path)) {
           throw new FileNotFoundException($path);
        }

        parent::__construct($path);
    }
    
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
    public function guessExtension()
    {
        $type = $this->getMimeType();
        $guesser = ExtensionGuesser::getInstance();

        return $guesser->guess($type);
    }
    
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
    public function getMimeType()
    {
        $guesser = MimeTypeGuesser::getInstance();

        return $guesser->guess($this->getPathname());
    }
    
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
    public function move($directory, $name = null)
    {
        $target = $this->getTargetFile($directory, $name);

        if (!rename($this->getPathname(), $target)) {
            $error = error_get_last();
            throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error['message'])));
        }
        chmod($target, 0666 & ~umask());

        return $target;
    }

    protected function getTargetFile($directory, $name = null)
    {
        if (!is_dir($directory)) {
            if (!FileManager::create($directory)) {
                throw new FileException(sprintf('Unable to create the "%s" directory', $directory));
            }
        } elseif (!is_writable($directory)) {
            throw new FileException(sprintf('Unable to write in the "%s" directory', $directory));
        }

        $target = rtrim($directory, '/\\').DIRECTORY_SEPARATOR.(null === $name ? $this->getBasename() : $this->getName($name));

        return $target;
    }

    /**
     * 
     * @param int $mode 
     * @return boolean
     */
    public function changeMode($mode)
    {
        return chmod($this->getPathname(), $mode);
    }
    
    /**
     * copies a file to another destination in the file system
     * @param string $destination a path to copy the file to
     * @return boolean
     */
    public function copyTo($destination)
    {
        return copy($this->getPathname(), $destination);
    }
    
    /**
     * deletes a file from the file system
     * @return boolean
     */
    public function delete()
    {
        return @unlink($this->getPathname());
    }
    
    /**
     * renames a file to another name
     * @param string $newname the new name of the renamed file
     * @return boolean
     */
    public function renameTo($newname)
    {
        return rename($this->getPathname(), $newname);
    }
    
    /**
     * checks if a file exists
     * @return boolean
     */
    public function exists()
    {
        return file_exists($this->file);
    }
    
    /**
     * Returns locale independent base name of the given path.
     *
     * @param string $name The new file name
     *
     * @return string containing
     */
    protected function getName($name)
    {
        $originalName = str_replace('\\', '/', $name);
        $pos = strrpos($originalName, '/');
        $originalName = false === $pos ? $originalName : substr($originalName, $pos + 1);

        return $originalName;
    }
}

