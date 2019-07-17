<?php

namespace Kaliba\Configure\Parsers;
use Kaliba\Configure\Parser;
use Kaliba\Support\Arraybag;


/**
 * Ini file Configureuration engine.
 *
 * Since IniConfigure uses parse_ini_file underneath, you should be aware that this
 * class shares the same behaviour, especially with regards to boolean and null values.
 *
 * In addition to the native `parse_ini_file` features, IniConfigure also allows you
 * to create nested array structures through usage of `.` delimited names. This allows
 * you to create nested arrays structures in an ini Configure file. For example:
 *
 * `db.password = secret` would turn into `['db' => ['password' => 'secret']]`
 *
 * You can nest properties as deeply as needed using `.`'s. In addition to using `.` you
 * can use standard ini section notation to create nested structures:
 *
 * ```
 * [section]
 * key = value
 * ```
 *
 * Once loaded into Configure, the above would be accessed using:
 *
 * `Configure::read('section.key');
 *
 * You can combine `.` separated values with sections to create more deeply
 * nested structures.
 *
 * IniConfigure also manipulates how the special ini values of
 * 'yes', 'no', 'on', 'off', 'null' are handled. These values will be
 * converted to their boolean equivalents.
 *
 * @see http://php.net/parse_ini_file
 *
 * 
 * This class is based on CakePHP(tm) Framework
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 */

class IniParser extends Parser
{
   
    /**
     *
     * @var string
     */
    protected $extension = 'ini';
        
    /**
     * Read method is used for reading Configureuration information from sources.
     * These sources can either be static resources like files, or dynamic ones like
     * a database, or other datasource.
     *
     * @return array An array of data to merge into the runtime Configureuration
     */
    public function read() {
        $file = $this->getPath($this->source);
        $contents = parse_ini_file($file, true);
        if (!empty($this->section) && isset($contents[$this->section])) {
            $values = $this->parseNestedValues($contents[$this->section]);
        } else {
            $values = [];
            foreach ($contents as $section => $attribs) {
                if (is_array($attribs)) {
                    $values[$section] = $this->parseNestedValues($attribs);
                } else {
                    $parse = $this->parseNestedValues([$attribs]);
                    $values[$section] = array_shift($parse);
                }
            }
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
        $result = [];
        foreach ($data as $key => $value) {
            $isSection = false;
            if ($key[0] !== '[') {
                $result[] = "[$key]";
                $isSection = true;
            }
            if(is_array($value)) {
                $keyValues = Arraybag::flatten($value, '.');
                foreach ($keyValues as $key2 => $value2) {
                    $result[] = "$key2 = " . $this->value($value2);
                }
            }
            if ($isSection) {
                $result[] = '';
            }
        }
        $contents = trim(implode("\n", $result));
        $file = $this->createAndGetPath($this->source);
        return file_put_contents($file, $contents) > 0;
    }

    /**
     * parses nested values out of keys.
     *
     * @param array $values Values to be exploded.
     * @return array Array of values exploded
     */
    protected function parseNestedValues($values){
        foreach ($values as $key => $value) {
            if ($value === '1') {
                $value = true;
            }
            if ($value === '') {
                $value = false;
            }
            unset($values[$key]);
            if (strpos($key, '.') !== false) {
                $values = Arraybag::insert($values, $key, $value);
            } else {
                $values[$key] = $value;
            }
        }
        return $values;
    }
    
    /**
     * Converts a value into the ini equivalent
     *
     * @param mixed $value Value to export.
     * @return string String value for ini file.
     */
    protected function value($value){
        if ($value === null) {
            return 'null';
        }
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        return (string)$value;

    }


}
