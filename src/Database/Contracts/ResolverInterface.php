<?php

namespace Kaliba\Database\Contracts;


interface ResolverInterface
{
    /**
     * Get a database connection instance.
     *
     * @param  string  $name
     * @return \Connectable
     */
    public function getConnection($name = null);

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection();

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name);
}
