<?php


namespace Kaliba\Database\Connections;


class MysqlConnection extends Connection
{

    /**
     *  String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $startQuote = '`';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $endQuote = '`';

    /**
     * Returns whether the driver supports adding or dropping constraints
     * to already created tables.
     *
     * @return bool true if driver supports dynamic constraints
     */
    public function supportsDynamicConstraints()
    {
        return true;
    }

    /**
     * Run SQL to disable foreign key checks.
     *
     * @return void
     */
    public function disableForeignKey()
    {
        return $this->execute('SET foreign_key_checks = 0')->closeCursor();
    }

    /**
     * Run SQL to enable foreign key checks.
     *
     * @return void
     */
    public function enableForeignKey()
    {
        return $this->execute('SET foreign_key_checks = 1')->closeCursor();
    }

}