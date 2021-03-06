<?php

namespace Kaliba\Support;

/* @description CI - A minimal Dependency Injection Container for PHP *
 * @author Tom Butler tom@r.je *
 * @copyright 2012-2018 Tom Butler <tom@r.je> | https:// r.je/dice.html *
 * @license http:// www.opensource.org/licenses/bsd-license.php BSD License *
 * @version 3.0 */
class Dice
{
    const FIXED = 'CI::FIXED';
    const GENERAL = 'CI::GENERAL';
    const INSTANCE = 'CI::INSTANCE';
    /**
     * @var array $rules Rules which have been set using addRule()
     */
    private $rules = [];

    /**
     * @var array $cache A cache of closures based on class name so each class is only reflected once
     */
    private $cache = [];

    /**
     * @var array $instances Stores any instances marked as 'shared' so create() can return the same instance
     */
    private $instances = [];
        
    /**
    * Add a rule $rule to the class $name
    * @param string $name The name of the class to add the rule for
    * @param array $rule The container can be fully configured using rules provided by associative arrays. See {@link https://r.je/dice.html#example3} for a description of the rules.
    */
    public function addRule(string $name, array $rule) 
    {
        if (isset($rule['instanceOf']) && (!array_key_exists('inherit', $rule) || $rule['inherit'] === true )) {
            $rule = array_replace_recursive($this->getRule($rule['instanceOf']), $rule);
     	}
     	//Allow substitutions rules to be defined with a leading a slash
     	if (isset($rule['substitutions'])){ 
            foreach($rule['substitutions'] as $key => $value)
            { 
                $rule[ltrim($key,  '\\')] = $value;
            }    
        }    
        $this->rules[ltrim(strtolower($name), '\\')] = array_replace_recursive($this->getRule($name), $rule);
     }

    /**
    * Add rules as array. Useful for JSON loading $dice->addRules(json_decode(file_get_contents('foo.json'));
    * @param array Rules in a single array [name => $rule] format
    */
    public function addRules($rules) {
    	if (is_string($rules)){
            $rules = json_decode(file_get_contents($rules), true);
        }
    	foreach ($rules as $name => $rule) {
            $this->addRule($name, $rule);
        }    
    }

    /**
     * Returns the rule that will be applied to the class $name when calling create()
     * @param string name The name of the class to get the rules for
     * @return array The rules for the specified class
     */
    public function getRule(string $name){
        $lcName = strtolower(ltrim($name, '\\'));
        if (isset($this->rules[$lcName])){ 
            return $this->rules[$lcName];
        }
        foreach ($this->rules as $key => $rule) { 							// Find a rule which matches the class described in $name where:
            if (empty($rule['instanceOf']) 		 							// It's not a named instance, the rule is applied to a class name
                    && $key !== '*' 				 							// It's not the default rule
                    && is_subclass_of($name, $key)								// The rule is applied to a parent class
                    && (!array_key_exists('inherit', $rule) || $rule['inherit'] === true )){ // And that rule should be inherited to subclasses
                    return $rule;}
        }
        // No rule has matched, return the default rule if it's set
        return isset($this->rules['*']) ? $this->rules['*'] : [];
    }

    /**
     * Returns a fully constructed object based on $name using $args and $share as constructor arguments if supplied
     * @param string name The name of the class to instantiate
     * @param array $args An array with any additional arguments to be passed into the constructor upon instantiation
     * @param array $share Whether or not this class instance be shared, so that the same instance is passed around each time
     * @return object A fully constructed object based on the specified input arguments
     */
    public function create(string $name, array $args = [], array $share = []) {
        // Is there a shared instance set? Return it. Better here than a closure for this, calling a closure is slower.
        if (!empty($this->instances[$name])){
            return $this->instances[$name];
        }
        // Create a closure for creating the object if there isn't one already
        if (empty($this->cache[$name])){
            $this->cache[$name] = $this->getClosure($name, $this->getRule($name));
        }
        // Call the cached closure which will return a fully constructed object of type $name
        return $this->cache[$name]($args, $share);
    }

    /**
     * Returns a closure for creating object $name based on $rule, caching the reflection object for later use
     * @param string $name the Name of the class to get the closure for
     * @param array $rule The container can be fully configured using rules provided by associative arrays. See {@link https://r.je/dice.html#example3} for a description of the rules.
     * @return callable A closure
     */
    private function getClosure(string $name, array $rule) {
        // Reflect the class and constructor, this should only ever be done once per class and get cached
        $className = isset($rule['instanceOf']) ? $rule['instanceOf'] : $name;
        $class = new \ReflectionClass($className);
        $constructor = $class->getConstructor();

        // Create parameter generating function in order to cache reflection on the parameters. This way $reflect->getParameters() only ever gets called once
        $params = $constructor ? $this->getParams($constructor, $rule) : null;
        //PHP throws a fatal error rather than an exception when trying to instantiate an interface, detect it and throw an exception instead
        if ($class->isInterface()){ 
            $closure = function() {
                throw new \InvalidArgumentException('Cannot instantiate interface');
            };           
        }
        // Get a closure based on the type of object being created: Shared, normal or constructorless
        else if ($params) {
            $closure = function (array $args, array $share) use ($class, $params) {
                // This class has depenencies, call the $params closure to generate them based on $args and $share
                return new $class->name(...$params($args, $share));
            };  
        }
        else{
            $closure = function () use ($class) { // No constructor arguments, just instantiate the class
                return new $class->name;
            };
            
        }
        if (!empty($rule['shared'])) {
            $closure = function (array $args, array $share) use ($class, $name, $constructor, $params, $closure) {
                //Internal classes may not be able to be constructed without calling the constructor and will not suffer from #7, construct them normally.
                if ($class->isInternal()){
                    $this->instances[$name] = $this->instances[ltrim($name, '\\')] = $closure($args, $share);
                }
                else {
                    //Otherwise, create the class without calling the constructor (and write to \$name and $name, see issue #68)
                    $this->instances[$name] = $this->instances[ltrim($name, '\\')] = $class->newInstanceWithoutConstructor();
                    // Now call this constructor after constructing all the dependencies. This avoids problems with cyclic references (issue #7)
                    if($constructor) {
                        $constructor->invokeArgs($this->instances[$name], $params($args, $share));
                    }
                    return $this->instances[$name];
                }
            
            };
        }    
        // If there are shared instances, create them and merge them with shared instances higher up the object graph
        if (isset($rule['shareInstances'])) {
            $closure = function(array $args, array $share) use ($closure, $rule) {
                foreach($rule['shareInstances'] as $instance) {
                    $share[] = $this->create($instance, [], $share);   
                }
                return $closure($args, $share);
            };
        }
        // When $rule['call'] is set, wrap the closure in another closure which will call the required methods after constructing the object
        // By putting this in a closure, the loop is never executed unless call is actually set
        return isset($rule['call']) ? function (array $args, array $share) use ($closure, $class, $rule) {
            // Construct the object using the original closure
            $object = $closure($args, $share);

            foreach ($rule['call'] as $call) {
                    // Generate the method arguments using getParams() and call the returned closure
                    $params = $this->getParams($class->getMethod($call[0]), ['shareInstances' => isset($rule['shareInstances']) ? $rule['shareInstances'] : [] ],($this->expand(isset($call[1]) ? $call[1] : [])));
                    $return = $object->{$call[0]}($params);
                    if (isset($call[2]) && is_callable($call[2])) call_user_func($call[2], $return);
            }
            return $object;
        } : $closure;
    }

    /**
     * Looks for CI::INSTANCE, CI::GENERAL or CI::FIXED array keys in $param and when found returns an object based on the value see {@link https:// r.je/dice.html#example3-1}
     * @param mixed $param Either a string or an array,
     * @param array $share Array of instances from 'shareInstances', required for calls to `create`
     * @param bool $createFromString
     * @return mixed
     */
    private function expand($param, array $share = [], bool $createFromString = false) {
        if (is_array($param)) {
            //if a rule specifies CI::INSTANCE, look up the relevant instance
            if (isset($param[self::INSTANCE])) {
                    //Check for 'params' which allows parameters to be sent to the instance when it's created
                    //Either as a callback method or to the constructor of the instance
                    $args = isset($param['params']) ? $this->expand($param['params']) : [];

                    //Support CI::INSTANCE by creating/fetching the specified instance
                    if (is_array($param[self::INSTANCE])) $param[self::INSTANCE][0] = $this->expand($param[self::INSTANCE][0], $share, true);
                    if (is_callable($param[self::INSTANCE])) return call_user_func($param[self::INSTANCE], ...$args);
                    else return $this->create($param[self::INSTANCE], array_merge($args, $share));
            }
            else if (isset($param[self::GENERAL])) return $param[self::GENERAL];
            else if (isset($param[self::FIXED])) return FIXED($param[self::FIXED]);
            else foreach ($param as $name => $value) $param[$name] = $this->expand($value, $share);
        }

        return is_string($param) && $createFromString ? $this->create($param) : $param;
    }

    /**
     * Returns a closure that generates arguments for $method based on $rule and any $args passed into the closure
     * @param object $method An instance of ReflectionMethod (see: {@link http:// php.net/manual/en/class.reflectionmethod.php})
     * @param array $rule The container can be fully configured using rules provided by associative arrays. See {@link https://r.je/dice.html#example3} for a description of the rules.
     * @return callable A closure that uses the cached information to generate the arguments for the method
     */
    private function getParams(\ReflectionMethod $method, array $rule) {
		// Cache some information about the parameter in $paramInfo so (slow) reflection isn't needed every time
		$paramInfo = [];
		foreach ($method->getParameters() as $param) {
			$class = $param->getClass() ? $param->getClass()->name : null;
			$paramInfo[] = [$class, $param, isset($rule['substitutions']) && array_key_exists($class, $rule['substitutions'])];
		}

		// Return a closure that uses the cached information to generate the arguments for the method
		return function (array $args, array $share = []) use ($paramInfo, $rule) {
			// Now merge all the possible parameters: user-defined in the rule via parameters, shared instances and the $args argument from $dice->create();
			if ($share || isset($rule['parameters'])) $args = array_merge($args, isset($rule['parameters']) ? $this->expand($rule['parameters'], $share) : [], $share);

			$parameters = [];

			// Now find a value for each method parameter
			foreach ($paramInfo as list($class, $param, $sub)) {
				// First loop through $args and see whether or not each value can match the current parameter based on type hint
				if ($args) foreach ($args as $i => $arg) { // This if statement actually gives a ~10% speed increase when $args isn't set
					if ($class && ($arg instanceof $class || ($arg === null && $param->allowsNull()))) {
						// The argument matched, store it and remove it from $args so it won't wrongly match another parameter
						$parameters[] = array_splice($args, $i, 1)[0];
						// Move on to the next parameter
						continue 2;
					}
				}
				// When nothing from $args matches but a class is type hinted, create an instance to use, using a substitution if set
				if ($class)	try {
					$parameters[] = $sub ? $this->expand($rule['substitutions'][$class], $share, true) : $this->create($class, [], $share);
				}
				catch (\InvalidArgumentException $e) {
				}
				// For variadic parameters, provide remaining $args
				else if ($param->isVariadic()) $parameters = array_merge($parameters, $args);
				// There is no type hint, take the next available value from $args (and remove it from $args to stop it being reused)
				// Support PHP 7 scalar type hinting,  is_a('string', 'foo') doesn't work so this is a hacky AF workaround: call_user_func('is_' . $type, '')
				else if ($args && (!$param->getType() || call_user_func('is_' . $param->getType()->__toString(), $args[0]))) $parameters[] = $this->expand(array_shift($args));
				// There's no type hint and nothing left in $args, provide the default value or null
				else $parameters[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
			}
			return $parameters;
		};
	}
}
