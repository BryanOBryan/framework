<?php

namespace Kaliba\Collection;

class CollectionIterator implements \Iterator
{
    
    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var int
     */
    protected $currentItem= 0;

    public function __construct(Collection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * @inheritDoc
     */
    public function current()
    {
        return $this->collection->get($this->currentItem);
    }

    /**
     * @inheritDoc
     */
    public function next()
    {
        $this->currentItem++;
    }

    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->currentItem;
    }

    /**
     * @inheritDoc
     */
    public function valid()
    {
        return null !== $this->collection->get($this->currentItem);
    }

    /**
     * @inheritDoc
     */
    public function rewind()
    {
        $this->currentItem = 0;
    }


}
