<?php

namespace Kaliba\Session;
use Kaliba\Database\Connections\Connection;

/**
 * Database Session save handler. Allows saving session information into a model.
 * DatabaseSession provides methods to be used with Session.
 *
 */
class DatabaseSessionHandler  implements \SessionHandlerInterface
{
    /**
     *
     * @var Connection
     */
    protected $connection;

    /**
     *
     * @var string
     */
    protected $table;

    /**
     * The existence state of the session.
     *
     * @var bool
     */
    protected $exists;

    /**
     * @var int
     */
    protected $lifetime;

    /**
     * DatabaseSessionHandler constructor.
     * @param Connection $connection
     * @param string $table
     * @param int $lifetime
     */
    public function __construct( Connection $connection, $table, $lifetime)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->lifetime = $lifetime;
    }
    
    /**
     * Initialize session
     *
     * @return boolean
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Close the session
     *
     * @return boolean
     */
    public function close()
    {
        unset($this->connection);
        return true;
    }

    /**
     * Read session data
     *
     * @param string $sessionId Session id
     * @return string Session data if available, empty string if not.
     */
    public function read($sessionId) 
    {
        $session = (object) $this->find($sessionId);
        if ($this->isAlive($session)) {
            $this->exists = true;
        }
        if (isset($session->payload)) {
            $this->exists = true;
            return base64_decode($session->payload);
        }
    }

    /**
     * Write session data
     *
     * @param string $sessionId Session id
     * @param string $data Session data to be written
     * @return type
     */
    public function write($sessionId, $data) 
    {
        $payload = $this->getPayload($sessionId, $data);

        if (! $this->exists) {
            $this->read($sessionId);
        }
        if ($this->exists) {
            $this->update($sessionId, $payload);
        } else {
            $this->insert($sessionId, $payload);
        }

        return $this->exists = true;
    }

    /**
     * Destroy a session
     *
     * @param string $sessionId Session id
     * @return type
     */
    public function destroy($sessionId) 
    {
        $stmt = $this->connection
            ->delete($this->table)
            ->where('sessionId', $sessionId)
            ->execute();
        return($stmt->rowCount() >= 1)? true:false;
    }

    /**
     * Cleanup old sessions
     * Garbage collection.
     *
     * @param string $maxlifetime Sessions that have not updated for the last maxlifetime seconds will be removed.
     * @return mixed The return value (usually TRUE on success, FALSE on failure).
     */
    public function gc($lifetime)
    {
        $timeout = time() - $lifetime;
        $stmt = $this->connection
            ->delete($this->table)
            ->greaterOrEqual('timeout', $timeout )
            ->execute();
        return ($stmt->rowCount() >= 1)?true:false;
    }


    /**
     * @return integer the number of seconds after which data will be seen as 'garbage' and cleaned up, defaults to 1440 seconds.
     */
    public function getTimeout()
    {
        return (int) ini_get('session.gc_maxlifetime');
    }


    /**
     * @param $sessionId
     * @return \stdClass
     */
    protected function find($sessionId)
    {
        return $this->connection
            ->select($this->table)
            ->where('sessionId', $sessionId)
            ->fetch();
    }

    /**
     * Perform an insert operation on the session ID.
     *
     * @param  string  $sessionId
     * @param  array  $payload
     * @return boolean
     */
    protected function insert($sessionId, $payload)
    {
        return $this->connection->insert($this->table)->values($payload)->execute()->rowCount();
    }

    /**
     * Perform an update operation on the session ID.
     *
     * @param  string  $sessionId
     * @param  array  $payload
     * @return boolean
     */
    protected function update($sessionId, $payload)
    {
        unset($payload['sessionId']);
        return $this->connection->update($this->table)->set($payload)
            ->where('sessionId', $sessionId)->execute()->rowCount();
    }

    /**
     * Get the default payload for the session.
     * @param string $sessionId
     * @param  string  $data
     * @return array
     */
    protected function getPayload($sessionId, $data)
    {
        $timeout = new \DateTime($this->getTimeout());
        $payload = [
            'sessionId' => $sessionId,
            'payload' => base64_encode($data),
            'timeout' => $timeout
        ];
        return $payload;
    }

    /**
    * Determine if the session is alive.
    *
    * @param  \stdClass  $session
    * @return bool
    */
    protected function isAlive($session)
    {
        $timeout = time() - $this->lifetime;
        return isset($session->timeout) && $session->timeout < $timeout;
    }


}
