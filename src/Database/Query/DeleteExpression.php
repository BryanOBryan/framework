<?php
namespace Kaliba\Database\Query;
use Kaliba\Database\Query\Concerns\HasConditions;
use Kaliba\Database\Query\QueryExpression;

class DeleteExpression extends QueryExpression
{
    use HasConditions;

    /**
     * Builds and renders the sql statement
     * @return string
     */
    public function getSql()
    {
        $sql = trim("DELETE FROM {$this->table} ");
        if(!empty($this->conditions)){
            $sql .= sprintf(' WHERE %s ', $this->conditions);
        }
        return $sql;
    }

}