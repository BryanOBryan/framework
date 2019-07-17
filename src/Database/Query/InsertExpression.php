<?php
namespace Kaliba\Database\Query;
use Kaliba\Database\Query\QueryExpression;

class InsertExpression extends QueryExpression
{
    /**
     *
     * @var array
     */
    protected $values = [];
    
     /**
     * Adds a table to be used in this query.
     * @see table
     * @param string $table tables to be added to query
     * @return $this
     */
    public function into($table)
    {
        return $this->table($table); 
    }

    /**
     * Set the values for an insert query.
     *
     * Multi inserts can be performed by calling values() more than one time,
     * or by providing an array of value sets. Additionally $data can be a Query
     * instance to insert data from another SELECT statement.
     *
     * @param array|string $data The data to insert.
     * @return $this
     */
    public function values($data)
    {
        if(is_array($data)){
            $this->values =  $data;
        }
        elseif(func_num_args() > 1){
            $this->values= func_get_args();
        }
        else{
            $this->values = (array)$data;
        }
        return $this;
    }
    
    /**
     * Builds and renders the sql statement
     * @return string
     */
    public function getSql()
    {
        $params = $this->getParams();
        return trim ("INSERT INTO {$this->table} VALUES ({$params}) ");
    }

    /**
     * Get placeholders for the INSERT Operation
     * @return string
     */
    private function getParams()
    {
        $binder = $this->binder();
        $placeholders = $binder->placeholders($this->values);
        $this->setBindings($binder->bindings());
        $params = implode(',', $placeholders);
        return $params;
    }

}