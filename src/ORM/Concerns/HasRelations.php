<?php

namespace Kaliba\ORM\Concerns;
use Kaliba\Support\Inflector;
use Kaliba\Collection\Collection;

trait HasRelations
{    
    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = [];
   
    /**
     * Define a one-to-one relationship.
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     * @return mixed 
     */
    protected function hasOne($model, $foreignKey=null, $localKey=null)
    {
        $class = $this->getClassFor($model);
        $table = $this->getTableFor($model);
        $pk = $localKey ?? $this->getPrimaryKey();
        $fk = $foreignKey?? $this->getForeignKey();
        return $this->mapper($table, $pk, $class)
            ->where($fk, $this->getKey())->limit(1)->item(0);
    }

    /**
     * Define a one-to-many relationship.
     * @param string $model
     * @@param string $foreignKey
     * @param string $localKey
     * @return Collection
     */
    protected function hasMany($model, $foreignKey=null, $localKey=null)
    {
        $class = $this->getClassFor($model);
        $table = $this->getTableFor($model);
        $pk = $localKey ?? $this->getPrimaryKey();
        $fk = $foreignKey?? $this->getForeignKey();
        $records = $this->mapper($table, $pk, $class)
            ->where($fk, $this->getKey())->fetch();
        return static::collect($records);
    }

    /**
     * Define an inverse one-to-one or many relationship.
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     * @return mixed
     */
    protected function belongsTo($model, $foreignKey=null, $localKey=null)
    {
        $class = $this->getClassFor($model);
        $table = $this->getTableFor($model);
        $pk = $localKey ?? $this->getPrimaryKey();
        $fk = $foreignKey?? $this->getForeignKeyFor($model);
        return $this->mapper($table, $pk, $class)
            ->get($this->getAttribute($fk));

    }

    /**
     * Define many-to-many relationship
     * @param string $intermediate
     * @param string $related
     * @param array $foreignKey
     * @param string $localKey
     * @return Collection
     */
    protected function belongsToMany($intermediate, $related, $foreignKey=[], $localKey=null)
    { 
        if(!empty($foreignKey)){
            $parentFK =  $foreignKey[0];
            $relatedFK = $foreignKey[1];           
        }else{
            $parentFK = $this->getForeignKey();
            $relatedFK = $this->getForeignKeyFor($related);
        }      
        $table = $this->getTableFor($intermediate);     
        $records = $this->mapper($table, [$parentFK, $relatedFK])
                ->where($parentFK, $this->getKey());
      
        $relation = $this->related($related, $localKey);
        $collection = array();
        foreach($records as $record){
            $collection[] = $relation->get($record->{$relatedFK});
        }
        return static::collect($collection);

    }
  
    /**
     * Load relation
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     */
    protected function loadRelation($model, $foreignKey=null, $localKey=null)
    {
        $class = $this->getClassFor($model);
        $table = $this->getTableFor($model);
        $pk  = $localKey ?? $this->getPrimaryKey();
        $fk = $foreignKey?? $this->getForeignKeyFor($model);
        $mapper = $this->mapper($table, $pk, $class);
        $this->getMapper()->hasOne($mapper,$fk,$pk);     
    }

    /**
     * Load relation that loads on each model instantiation
     */
    protected function loadRelations()
    {
        foreach ($this->with as $relation){
            $this->loadRelation($relation);
        }
    }
    
    /**
     * Get related Mapper
     * @param string $related
     * @param string $localKey
     * @return Mapper
     */
    private function related($related, $localKey= null)
    {
        $class = $this->getClassFor($related);
        $table = $this->getTableFor($related);
        $pk = $localKey ?? $this->getPrimaryKey();
        return $this->mapper($table, $pk, $class);
    }    
  
    /**
     * Get Class name for the relation model
     * @param string $relation
     * @return string
     */
    private function getClassFor($relation)
    {
        if(class_exists($relation)){
            return $relation;
        }
    }

    /**
     * Get foreign key for a relation model
     * @param string $relation
     * @return string
     */
    private function getForeignKeyFor($relation)
    {
        $name = $this->getTableFor($relation);
        $singular = Inflector::singularize($name);
        return strtolower(Inflector::delimit($singular).'_id');
    }

    /**
     * Get tablename for a relation model
     * @param string $relation
     * @return mixed|string
     */
    private function getTableFor($relation)
    {
        if(class_exists($relation)){
            $array = explode("\\", $relation) ;
            $name = end($array);
            return Inflector::tableize($name);
        }else{
            return Inflector::tableize($relation);
        }


    }



}

