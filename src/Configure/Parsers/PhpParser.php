<?php

namespace Kaliba\Configure\Parsers;
use Kaliba\Configure\Parser;
use Exception;

/**
 * PHP engine allows Configure to load Configureuration values from
 * files containing simple PHP arrays.
 *
 * Files compatible with PhpConfigure should return an array that
 * contains all of the Configureuration data contained in the file.
 * 
 * This class is part of the CakePHP(tm) Framework
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 */
class PhpParser extends Parser
{
    /**
     *
     * @var string
     */
    protected $extension = 'php';
    
    /**
     * Read method is used for reading Configureuration information from sources.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     *
     * @return array An array of data to merge into the runtime Configureuration
     */
    public function read() {
        $file = $this->getPath($this->source);
        $return = include $file;
        if (is_array($return)) {
            return $return;
        }
        if (!isset($return)) {
            throw new Exception(sprintf('Configure file "%s" did not return an array', $this->source));
        }

        return $return;
    }

    /**
     * Dumps the Configureure data into source.
     *
     * @param array $data The data to dump.
     * @return bool True on success or false on failure.
     */
    public function write(array $data) {
        $contents = '<?php' . "\n" . 'return ' . var_export($data, true) . ';';
        $file = $this->createAndGetPath($this->source);
        return file_put_contents($file, $contents) > 0;
    }


}
