<?php
namespace Kaliba\Session;
use Exception;
use Kaliba\Filesystem\FileManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileObject;


/**
 * handlerSession provides method for saving sessions into a handler engine. Used with Session
 *
 */
class FileSessionHandler implements \SessionHandlerInterface
{	
    /**
     * handler object
     * @var FileEngine
     */
    protected $fileManager;

    /**
     * The path where sessions should be stored.
     *
     * @var string
     */
    protected $path;

    /**
     *
     * @var int
     */
    protected $lifetime;

    /**
     * FileSessionHandler constructor.
     * @param FileManager $fileManager
     * @param string $path
     * @param int $lifetime
     */
    public function __construct(FileManager $fileManager, $path, $lifetime)
    {
        $this->fileManager = $fileManager;
        $this->path = $path;
        $this->lifetime = $lifetime;
        if(!is_dir($this->path)){
            $this->fileManager->makeDirectory($this->path);
        }
    }
    
    /**
     * Method called on open of a database session.
     *
     * @param string $path The path where to store/retrieve the session.
     * @param string $name The session name.
     * @return bool Success
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Method called on close of a database session.
     *
     * @return bool Success
     */
    public function close()
    {
        return true;
    }

    /**
     * Method used to read from a handler session.
     *
     * @param string $sessionId The key of the value to read
     * @return mixed The value of the key or false if it does not exist
     */
    public function read($sessionId)
    {
        $threshold = time() - $this->lifetime;
        if ($this->fileManager->exists($path = $this->path.'/'.$sessionId)) {
            if (filemtime($path) >= $threshold) {
                return $this->fileManager->read($path, true);
            }
        }
        return '';
    }

    /**
     * Helper function called on write for handler sessions.
     *
     * @param int $sessionId ID that uniquely identifies session in database
     * @param mixed $data The value of the data to be saved.
     * @return bool True for successful write, false otherwise.
     */
    public function write($sessionId, $data)
    {
        $this->fileManager->put($this->path.'/'.$sessionId, $data, true);
        return true;
    }

    /**
     * Method called on the destruction of a handler session.
     *
     * @param int $sessionId ID that uniquely identifies session in handler
     * @return bool True for successful delete, false otherwise.
     */
    public function destroy($sessionId)
    {
        $this->fileManager->deleteR($this->path.'/'.$sessionId);
        return true;
    }

    /**
     * Helper function called on gc for handler sessions.
     *
     * @param string $lifetime Sessions that have not updated for the last lifetime seconds will be removed.
     * @return bool True (irrespective of whether or not the garbage is being successfully collected)
     */
    public function gc($lifetime)
    {
        $threshold = time() - $lifetime;
        $files = $this->fileManager->search($this->path);
        foreach ($files as $file){
            if ($file->getMTime() <= $threshold) {
                $this->fileManager->deleteR($file->getRealPath());
            }
        }
        return true;
    }
	
    /**
     * @return integer the number of seconds after which data will be seen as 'garbage' and cleaned up, defaults to 1440 seconds.
     */
    public function getTimeout() 
    {
        return (int) ini_get('session.gc_maxlifetime');
    }

}
