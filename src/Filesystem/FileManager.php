<?php

namespace Kaliba\Filesystem;
use Kaliba\Filesystem\Contracts\FileManagerInterface;
use Kaliba\Filesystem\Exceptions\FileNotFoundException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;
use RegexIterator;
use Exception;

/**
 * File Manager gives an object oriented way to interact with the file system by creating, searching and deleting files from the file system
 */
class FileManager
{
	
    /**
     * Create a directory in the filesystem 
     * @param string $folder folder name to create
     * @param int $mode directory mode
     * @param boolean $recursive whether to create recursively
     */
    public function makeDirectory($folder, $mode=0777, $recursive=FALSE)
    {
        return mkdir($folder, $mode, $recursive);
    }
    
    /**
     * search files and folders in the file system recursively
     * @param string $folder base folder to search in
     * @param string $pattern file or pattern to search in the given folder 
     * @param int $depth Depth of directories to traverse(-1=any depth, 0 = fully recursive, 1 = current dir, etc)
     * @param int $flags Description
     * @return RegexIterator|RecursiveIteratorIterator
     */
    public function searchR($folder, $pattern=null, $depth=-1, $flags=RecursiveIteratorIterator::SELF_FIRST)
    {
        
        try{
            $dir_iter = new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS);
            $flat_iter = new RecursiveIteratorIterator($dir_iter, $flags);
            $flat_iter->setMaxDepth($depth);
            if(is_null($pattern)){
                return $flat_iter;
            }
            else {
                return new RegexIterator($flat_iter, $pattern);
            } 
        } catch (Exception $ex) {
            print $ex->getMessage();
        }
            
    }
    
    /**
     * search files and folders in the file system
     * @param string $folder base folder to search in
     * @param string $pattern file or pattern to search in the given folder 
     * @return RegexIterator|RecursiveIteratorIterator
     */
    public function search($folder, $pattern=null)
    {
        return $this->searchR($folder, $pattern, 0);
    }
    
    /**
     * delete files and folders in the file system recursively
     * @param string $path folder or file
     * @param string $pattern file or pattern to search in the given folder 
     * @param int $depth Depth of directories to traverse(-1=any depth, 0 = fully recursive, 1 = current dir, etc)
     * @return boolean
     */
    public function deleteR($path, $pattern=null, $depth=-1)
    {
        foreach($this->searchR($path, $pattern, $depth, \RecursiveIteratorIterator::CHILD_FIRST) as $file){
            if($file->isFile()){
                unlink($file);
            }   
            elseif($file->isDir()){
                rmdir($file);
            }
        }
    }


    /**
     * Determine if a file or directory exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * Get the MD5 hash of the file at the given path.
     *
     * @param  string  $path
     * @return string
     */
    public function hash($path)
    {
        return md5_file($path);
    }

    /**
     * Get the contents of a file.
     *
     * @param  string  $path
     * @param  bool  $lock
     * @return string
     *
     * @throws FileNotFoundException
     */
    public function read($path, $lock = false)
    {
        if ($this->isFile($path)) {
            return $lock ? $this->readShared($path) : file_get_contents($path);
        }

        throw new FileNotFoundException($path);
    }

    /**
     * Get contents of a file with shared access.
     *
     * @param  string  $path
     * @return string
     */
    public function readShared($path)
    {
        $contents = '';

        $handle = fopen($path, 'rb');

        if ($handle) {
            try {
                if (flock($handle, LOCK_SH)) {
                    clearstatcache(true, $path);

                    $contents = fread($handle, $this->size($path) ?: 1);

                    flock($handle, LOCK_UN);
                }
            } finally {
                fclose($handle);
            }
        }

        return $contents;
    }


    /**
     * Write the contents of a file.
     *
     * @param  string  $path
     * @param  string  $contents
     * @param  bool  $lock
     * @return int
     */
    public function put($path, $contents, $lock = false)
    {
        return file_put_contents($path, $contents, $lock ? LOCK_EX : 0);
    }

    /**
     * Prepend to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function prepend($path, $data)
    {
        if ($this->exists($path)) {
            return $this->put($path, $data.$this->get($path));
        }

        return $this->put($path, $data);
    }

    /**
     * Append to a file.
     *
     * @param  string  $path
     * @param  string  $data
     * @return int
     */
    public function append($path, $data)
    {
        return file_put_contents($path, $data, FILE_APPEND);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     *
     * @param  string  $path
     * @param  int  $mode
     * @return mixed
     */
    public function chmod($path, $mode = null)
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }

    /**
     * Move a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function move($path, $target)
    {
        return rename($path, $target);
    }

    /**
     * Copy a file to a new location.
     *
     * @param  string  $path
     * @param  string  $target
     * @return bool
     */
    public function copy($path, $target)
    {
        return copy($path, $target);
    }

    /**
     * Create a hard link to the target file or directory.
     *
     * @param  string  $target
     * @param  string  $link
     * @return void
     */
    public function link($target, $link)
    {
        if (! windows_os()) {
            return symlink($target, $link);
        }

        $mode = $this->isDirectory($target) ? 'J' : 'H';

        exec("mklink /{$mode} \"{$link}\" \"{$target}\"");
    }

    /**
     * Extract the file name from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function name($path)
    {
        return pathinfo($path, PATHINFO_FILENAME);
    }

    /**
     * Extract the trailing name component from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function basename($path)
    {
        return pathinfo($path, PATHINFO_BASENAME);
    }

    /**
     * Extract the parent directory from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function dirname($path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }

    /**
     * Extract the file extension from a file path.
     *
     * @param  string  $path
     * @return string
     */
    public function extension($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Get the file type of a given file.
     *
     * @param  string  $path
     * @return string
     */
    public function type($path)
    {
        return filetype($path);
    }

    /**
     * Get the mime-type of a given file.
     *
     * @param  string  $path
     * @return string|false
     */
    public function mimeType($path)
    {
        return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
    }

    /**
     * Get the file size of a given file.
     *
     * @param  string  $path
     * @return int
     */
    public function size($path)
    {
        return filesize($path);
    }

    /**
     * Get the file's last modification time.
     *
     * @param  string  $path
     * @return int
     */
    public function lastModified($path)
    {
        return filemtime($path);
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param  string  $directory
     * @return bool
     */
    public function isDirectory($directory)
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is readable.
     *
     * @param  string  $path
     * @return bool
     */
    public function isReadable($path)
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     *
     * @param  string  $path
     * @return bool
     */
    public function isWritable($path)
    {
        return is_writable($path);
    }

    /**
     * Determine if the given path is a file.
     *
     * @param  string  $file
     * @return bool
     */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Find path names matching a given pattern.
     *
     * @param  string  $pattern
     * @param  int     $flags
     * @return array
     */
    public function glob($pattern, $flags = 0)
    {
        return glob($pattern, $flags);
    }

    /**
     * Get an array of all files in a directory.
     *
     * @param  string  $directory
     * @return array
     */
    public function files($directory)
    {
        $glob = glob($directory.DIRECTORY_SEPARATOR.'*');

        if ($glob === false) {
            return [];
        }

        // To get the appropriate files, we'll simply glob the directory and filter
        // out any "files" that are not truly files so we do not end up with any
        // directories in our list, but only true files within the directory.
        return array_filter($glob, function ($file) {
            return filetype($file) == 'file';
        });
    }
}

