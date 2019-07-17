<?php
namespace Kaliba\Configure\Contracts;

interface ParserInterface 
{
    /**
     * get method is used to get Configureuration source or file.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     *
     * @return string source of Configureuration
     */
    public function getSource();

    /**
     * set method is used to set Configureuration data source or file.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     * @param string $source of Configureuration
     */
    public function setSource($source);
    
    /**
     * Read method is used for reading Configureuration information from sources.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     *
     * @return array An array of data to merge into the runtime Configureuration
     */
    public function read();

    /**
     * Dumps the Configure data into source.
     *
     * @param array $data The data to dump.
     * @return bool True on success or false on failure.
     */
    public function write(array $data);
}
