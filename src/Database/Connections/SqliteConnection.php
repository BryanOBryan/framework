<?php


namespace Kaliba\Database\Connections;


class SqliteConnection extends Connection
{
    /**
     *  String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $startQuote = '"';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $endQuote = '"';

    /**
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool true if driver supports dynamic constraints
     */
    public function supportsDynamicConstraints()
    {
        return false;
    }

    /**
     * Run SQL to disable foreign key checks.
     *
     * @return void
     */
    public function disableForeignKey()
    {
        return $this->execute('PRAGMA foreign_keys = OFF')->closeCursor();
    }

    /**
     * Run SQL to enable foreign key checks.
     *
     * @return void
     */
    public function enableForeignKey()
    {
        return $this->execute('PRAGMA foreign_keys = ON')->closeCursor();
    }

}