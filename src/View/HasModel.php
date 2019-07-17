<?php

namespace Kaliba\View;
use Kaliba\ORM\Model;
use Kaliba\Collection\Collection;

trait HasModel
{
    /**
     *
     * @var Model|Collection|array
     */
    protected $model;

    /**
     * Set Model instance
     * @param Model|Collection|array $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Get Model Instance
     * @return Model|Collection|array
     */
    public function getModel()
    {
        return $this->model;
    }

}