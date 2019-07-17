<?php
namespace Kaliba\Database\Query;

use Kaliba\Database\Query\Concerns\HasConditions;
use Kaliba\Database\Query\QueryExpression;

class UpdateExpression extends QueryExpression
{
    use HasConditions;

    /**
     * @var array
     */
    protected $updates = [];
    
    /**
     * Set one or many fields to update.
     *
     * @param string|array $key The column name or array of keys
     * @param string $value The value to update $key to. 
     * @return $this
     */
    public function set($key, $value=null)
    {
       if(is_string($key) ){
            if(empty($this->updates[$key])){
                $this->updates[$key] = $value;
            } 
        }
        elseif(is_array($key)){
            $this->updates = $key;
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
        return trim("UPDATE {$this->table} SET {$params} WHERE {$this->conditions}");
    }

    /**
     * Get placeholders for the UPDATE Operation
     * @return string
     */
    private function getParams()
    {
        $binder = $this->binder();
        $placeholders = [];
        foreach($this->updates as $column => $value){
            if(!empty($value)){
                $field = $this->identify($column);
                $placeholder = $binder->placeholder($column);
                $binder->bind($placeholder, $value);
                $this->setBindings($binder->bindings());
                $placeholders[] = sprintf( "%s = %s ", $field,$placeholder);
            }
        }
        return implode(', ', $placeholders);
    }

}