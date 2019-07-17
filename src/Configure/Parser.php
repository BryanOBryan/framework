<?php

namespace Kaliba\Configure;
use Kaliba\Configure\Contracts\ParserInterface;
use Kaliba\Support\Text;

abstract class Parser implements ParserInterface
{
    /**
     *
     * @var string
     */
    protected $extension;
    
    /**
     *
     * @var string
     */
    protected $source;
           
    /**
     *
     * @var string
     */
    protected $section;
    
    /**
     * Build and construct a new Configure file parser. The parser can be used to read
     * Configure files that are on the file system.
     *
     * @param string $source Path to load Configure files from.
     * @param string|null $section (for ini Configure file) Only get one section, leave null to parse and fetch all sections in the ini file.
     */
    public function __construct($source, $section = null)
    {  
        $this->source = $source;  
        $this->section = $section;
        
    }
   
    /**
     * Get file path
     * @param string $key The identifier to write to.
     * @return string Full file path  
     * @throws Exception
     */
    protected function getPath($key)
    {   
        $filename = basename($key);
        if(strpos($filename, '..') !== false) {
            throw new \Exception("Cannot load/write Configureuration files with ../ in them.");
        }
        list($file, $extension) = $this->split($filename);
        if($file && $extension){
            if($extension !== $this->extension){
                throw new \Exception("Cannot load/write Configureuration files with '$extension' extension. The required extension is '$this->extension' ");
            }
        }
        if(!is_readable($key)){
            throw new \Exception("Configuration file not found"); 
        }
        return $key;
    }
	
    /**
     * 
     * @param string $file
     * @return string
     * @throws \Exception
     */
    protected function createAndGetPath($file)
    {
        if(!file_exists($file) ){
            $handle = fopen($file, 'w');
            if(!$handle){
                throw new \Exception("Could not create file");
            }
        }
        return $file;
    }

    /**
     * @param $file
     * @return array
     */
    protected function split($file)
    {
        return Text::splitPlugin($file);
    }

    /**
     * get method is used to get Configureuration source or file.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     *
     * @return string source of Configureuration
     */
    public function getSource() 
    {
        return $this->source;
    }

    /**
     * set method is used to set Configureuration data source or file.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     * @param string $source of Configureuration
     */
    public function setSource($source) 
    {
        $this->source = $source; 
    }
    
}
