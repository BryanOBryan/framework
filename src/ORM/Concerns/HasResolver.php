<?php


namespace Kaliba\ORM\Concerns;
use Kaliba\Database\Connections\Connection;
use Kaliba\Database\Contracts\ResolverInterface;

trait HasResolver
{
    /**
     * The connection resolver instance.
     *
     * @var ResolverInterface
     */
    protected static $resolver;

    /**
     * Resolve a connection instance.
     *
     * @param  string|null  $name Connection name to retrieve
     * @return Connection
     */
    public static function resolve($name = null)
    {
        return static::$resolver->getConnection($name);
    }

    /**
     * Set the connection resolver instance.
     *
     * @param  ResolverInterface  $resolver
     * @return void
     */
    public static function register($resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
     *
     * @return void
     */
    public static function unregister()
    {
        static::$resolver = null;
    }

}