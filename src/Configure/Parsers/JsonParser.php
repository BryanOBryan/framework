<?php

namespace Kaliba\Configure\Parsers;
use Kaliba\Configure\Parser;

/**
 * JSON engine allows Configure to load Configureuration values from
 * files containing JSON strings.
 * 
 * This class is part of the CakePHP(tm) Framework
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 */

class JsonParser extends Parser
{
    
    /**
     *
     * @var string
     */
    protected $extension = 'json';
       
    /**
     * Read method is used for reading Configureuration information from sources.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     *
     * @return array An array of data to merge into the runtime Configureuration
     */
    public function read() {
        $file = $this->getPath($this->source);
        $values = json_decode(file_get_contents($file), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(sprintf(
                "Error parsing JSON string fetched from Configure file \"%s.json\": %s",
                $file,
                json_last_error_msg()
            ));
        }
        if (!is_array($values)) {
            throw new Exception(sprintf(
                'Decoding JSON Configure file "%s.json" did not return an array',
                $file
            ));
        }
        return $values;
    }
    
    /**
     * Dumps the Configureure data into source.
     *
     * @param array $data The data to dump.
     * @return bool True on success or false on failure.
     */
    public function write(array $data) {
        $file = $this->createAndGetPath($this->source);
        return file_put_contents($file, json_encode($data)) > 0;
    }

}
