<?php
namespace Kaliba\Database\Query;
use Kaliba\Database\Query\Concerns\HasConditions;
use Kaliba\Database\Query\Concerns\HasFunctions;
use Kaliba\Database\Query\Concerns\HasJoins;
use Kaliba\Database\Query\QueryExpression;

class SelectExpression extends QueryExpression
{
    use HasJoins;
    use HasConditions;
    use HasFunctions;

    /**
     * Builds and renders the sql statement
     * @return string
     */
    public function getSql()
    {
        if(empty($this->columns)){
            $this->columns('*');
        }

        $sql = '';
        if(!empty($this->functions)){
            $sql = trim("SELECT {$this->functions} FROM {$this->table}");
        }else{
            $sql = trim("SELECT {$this->columns} FROM {$this->table}");
        }
        if(!empty($this->joins)){
            $sql .= sprintf(' %s ', $this->joins);
        }
        if(!empty($this->conditions)){
            $sql .= sprintf(' WHERE %s ', $this->conditions);
        }
        if(!empty($this->groupBy)){
            $sql .= sprintf(' %s ', $this->groupBy);
        }
        if(!empty($this->orderBy)){
            $sql .= sprintf(' %s ', $this->orderBy);
        }
        if(!empty($this->limit)){
            $sql .= sprintf(' LIMIT %s ', $this->limit);
        }
        if(!empty($this->offset)){
            $sql .= sprintf(' OFFSET %s ', $this->offset);
        }
        return $sql;

    }
    
    /**
     * Execute the query statement and return A single object
     * @return \stdClass
     */
    public function fetch(int $fetchMode = null)
    {
        if(is_null($fetchMode)){
            $fetchMode = \PDO::FETCH_OBJ;
        }
        $stmt = $this->execute();
        $stmt->setFetchMode($fetchMode);
        return $stmt->fetch();
    }
    
    /**
     * Execute the query statement and return an array of Objects
     * @return array
     */
    public function fetchAll(int $fetchMode = null)
    {
        if(is_null($fetchMode)){
            $fetchMode = \PDO::FETCH_OBJ;
        }
        $stmt = $this->execute();
        $stmt->setFetchMode($fetchMode);
        return $stmt->fetchAll();
    }

}