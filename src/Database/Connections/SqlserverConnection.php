<?php


namespace Kaliba\Database\Connections;


class SqlserverConnection extends Connection
{

    /**
     * String used to start a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $startQuote = '[';

    /**
     * String used to end a database identifier quoting to make it safe
     *
     * @var string
     */
    protected $endQuote = ']';

    /**
     * Creates a new save point for nested transactions.
     *
     * @param string $name The save point name.
     * @return void
     */
    public function createSavePoint($name)
    {
        return $this->execute('SAVE TRANSACTION t' . $name)->closeCursor();
    }

    /**
     * Releases a save point by its name.
     *
     * @param string $name The save point name.
     * @return void
     */
    public function releaseSavePoint($name)
    {
        return $this->execute('COMMIT TRANSACTION t' . $name)->closeCursor();
    }

    /**
     * Rollback a save point by its name.
     *
     * @param string $name The save point name.
     * @return void
     */
    public function rollbackSavePoint($name)
    {
        return $this->execute('ROLLBACK TRANSACTION t' . $name)->closeCursor();
    }

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
        return $this->execute('EXEC sp_msforeachtable "ALTER TABLE ? NOCHECK CONSTRAINT all"')->closeCursor();
    }

    /**
     * Run SQL to enable foreign key checks.
     *
     * @return void
     */
    public function enableForeignKey()
    {
        return $this->execute('EXEC sp_msforeachtable "ALTER TABLE ? WITH CHECK CHECK CONSTRAINT all"')->closeCursor();
    }

}