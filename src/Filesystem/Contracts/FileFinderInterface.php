<?php

namespace Kaliba\Filesystem\Contracts;

interface FileFinderInterface
{
    /**
     * search files from a directory and return file paths or SplFileObjects
     * The file objects gives you an object oriented way of manipulating the returned files
     * @param string $folder base folder to search in
     * @param string $fileorpattern specific file or pattern to search in the given folder 
     * @param array $exclusion file extensions to be excluded
     * @param boolean $objects whether to load file paths or SplFileObjects. true for objects, false for file paths 
     * @return @return Kaliba\Collection\Collection collection of file paths or SplFileObjects
     */
    public function find($folder, $fileorpattern=null, array $exclusion=[], $objects=false);
      
    /**
     * search files from a directory and load them 
     * @param string|array $folders a single directory or multiple directories to load files from
     * @param string $fileorpattern specific file or pattern to search in the given folder 
     */
    public function load($folders, $fileorpattern = null);
    
}
