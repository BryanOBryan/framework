<?php

namespace Kaliba\Foundation;
use Kaliba\Configure\Config;
use Kaliba\Configure\ConfigProvider;
use Kaliba\Error\ErrorHandlerProvider;
use Kaliba\Http\Exception\HttpException;
use Kaliba\Session\SessionProvider;
use Kaliba\Support\Container;
use Kaliba\Support\Dice;
use Kaliba\View\ViewProvider;
use RuntimeException;

class Application extends Container implements \ArrayAccess
{

    /**
     * The Kaliba framework version.
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * The base path for the Laravel installation.
     *
     * @var string
     */
    protected $basePath;

    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = [];

    /**
     * The current globally available container (if any).
     *
     * @var static
     */
    protected static $instance;

    /**
     * Create a new Illuminate application instance.
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath)
    {
        parent::__construct();
        $this->setBasePath($basePath);
        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCustomServiceProviders();
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Set the base path for the application.
     *
     * @param  string  $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
        return $this;
    }

    /**
     * Get the base path of the Kaliba installation.
     *
     * @param string $path Optionally, a path to append to the base path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->basePath.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the application configuration files.
     *
     * @param string $path Optionally, a path to append to the config path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'config'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the database directory.
     *
     * @param string $path Optionally, a path to append to the database path
     * @return string
     */
    public function databasePath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'database'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the storage directory.
     * @param string $path Optionally, a path to append to the storage path
     * @return string
     */
    public function storagePath($path='')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'storage'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the upload directory.
     * @param string $path Optionally, a path to append to the storage path
     * @return string
     */
    public function uploadPath($path='')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'uploads'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the resources directory.
     *
     * @param  string  $path
     * @return string
     */
    public function resourcePath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'resource'.($path ? DIRECTORY_SEPARATOR.$path : $path);
    }

    /**
     * Get the path to the language files.
     *
     * @return string
     */
    public function languagePath()
    {
        return $this->resourcePath().DIRECTORY_SEPARATOR.'language';
    }

    /**
     * Instantiate a concrete instance of the given type.
     *
     * @param  string  $concrete
     * @return mixed
     *
     */
    public function make($class, $parameters=[])
    {
        $dice = new Dice();
        return $dice->create($class, $parameters, [$this]);
    }

    /**
     * Get the application namespace.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getNamespace()
    {
        if ($this->config('app.namespace') != null) {
            return $this->config('app.namespace');
        }
        throw new RuntimeException('Unable to detect application namespace.');
    }

    /**
     * Get the application middleware
     *
     * @return array
     *
     */
    public function getMiddleware($name=null)
    {
        if(!empty($name)){
            return $this->config('middleware.'.$name);
        }
        return $this->config('middleware');
    }

    /**
     * Get the Configuration Instance
     *
     * @return Config
     *
     * @throws \RuntimeException
     */
    public function config($name=null)
    {
        if(empty($name) && $this->has('config')){
            return $this->get('config');
        }
        if (!empty($name) && $this->has('config')) {
            return $this->get('config')->get($name);
        }
        throw new RuntimeException('Unable to detect configurations.');
    }

    /**
     * Handle an HttpException with the given data.
     * @param HttpException
     */
    public function handle(HttpException $exception)
    {
        if($exception->getCode() != 0){
            $template = "error.{$exception->getCode()}";
            $this->get('view')->render($template);
        }else{
            $message = $exception->getMessage();
            $this->get('view')->render('error.default', compact('message'));
        }
    }

    /**
     * Determine if a given offset exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Get the value at a given offset.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set the value at a given offset.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Unset the value at a given offset.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key){}

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders()
    {
        $this->register(new ConfigProvider($this));
        $this->register(new ErrorHandlerProvider($this));
        $this->register(new SessionProvider($this));

    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    public function registerCustomServiceProviders()
    {
        $providers = $this->config('app.providers');
        foreach ($providers as $name){
            $provider = $this->make($name);
            $this->register($provider);
        }

    }

    /**
     * Register a service provider with the application.
     *
     * @param  ServiceProvider|string  $provider
     * @param  array  $options
     * @param  bool   $force
     * @return ServiceProvider
     */
    protected function register($provider, $options = [])
    {
        if (($registered = $this->getProvider($provider))) {
            return $registered;
        }
        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->make($provider);
        }

        if (method_exists($provider, 'register')) {
            call_user_func([$provider, 'register']);
        }
        $this->mark($provider);

    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  ServiceProvider|string  $provider
     * @return ServiceProvider|null
     */
    protected function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);
        if(in_array($name, $this->serviceProviders)){
            return $this->make($name);
        }
    }

    /**
     * Mark the given provider as registered.
     *
     * @param  ServiceProvider  $provider
     * @return void
     */
    protected function mark($provider)
    {
        $this->serviceProviders[] = $provider;
    }

    /**
     * Set the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Set the shared instance of the container.
     *
     * @param  Application
     * @return static
     */
    public static function setInstance(Application $application)
    {
        return static::$instance = $application;
    }

    /**
     * Dynamically access container services.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this[$key];
    }

    /**
     * Dynamically set container services.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this[$key] = $value;
    }



}