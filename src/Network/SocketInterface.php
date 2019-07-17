<?php

namespace Kaliba\Network;


/**
 *
 * Core base class for network communication.
 * This class is derived from CakePHP 
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 */
interface SocketInterface
{

    /**
     * Connect the socket to the given host and port.
     *
     * @return bool Success
     * @throws \Kaliba\Http\Exceptions\SocketException
     */
    public function connect();

    /**
     * Get the connection context.
     *
     * @return null|array Null when there is no connection, an array when there is.
     */
    public function context();

    /**
     * Get the host name of the current connection.
     *
     * @return string Host name
     */
    public function host();

    /**
     * Get the IP address of the current connection.
     *
     * @return string IP address
     */
    public function address();

    /**
     * Get all IP addresses associated with the current connection.
     *
     * @return array IP addresses
     */
    public function addresses();
    
    /**
     * Get the last error as a string.
     *
     * @return string|null Last error
     */
    public function lastError();

    /**
     * Set the last error.
     *
     * @param int $errNum Error code
     * @param string $errStr Error string
     * @return void
     */
    public function setLastError($errNum, $errStr);

    /**
     * Write data to the socket.
     *
     * @param string $data The data to write to the socket
     * @return bool Success
     */
    public function write($data);

    /**
     * Read data from the socket. Returns false if no data is available or no connection could be
     * established.
     *
     * @param int $length Optional buffer length to read; defaults to 1024
     * @return mixed Socket data
     */
    public function read($length = 1024);

    /**
     * Disconnect the socket from the current connection.
     *
     * @return bool Success
     */
    public function disconnect();

    /**
     * Destructor, used to disconnect from current connection.
     */
    public function __destruct();

    /**
     * Resets the state of this Socket instance to it's initial state (before Object::__construct got executed)
     *
     * @param array $state Array with key and values to reset
     * @return bool True on success
     */
    public function reset($state = null);

    /**
     * Encrypts current stream socket, using one of the defined encryption methods
     *
     * @param string $type can be one of 'ssl2', 'ssl3', 'ssl23' or 'tls'
     * @param string $clientOrServer can be one of 'client', 'server'. Default is 'client'
     * @param bool $enable enable or disable encryption. Default is true (enable)
     * @return bool True on success
     * @throws \InvalidArgumentException When an invalid encryption scheme is chosen.
     * @throws \Kaliba\Http\Exception\SocketException When attempting to enable SSL/TLS fails
     * @see stream_socket_enable_crypto
     */
    public function enableCrypto($type, $clientOrServer = 'client', $enable = true);
}
