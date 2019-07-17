<?php
namespace Kaliba\ORM\Contracts;

interface Relation 
{
    public function getData($parentObject);
    public function overwrite($parentObject, $data);
}