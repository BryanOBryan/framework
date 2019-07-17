<?php

namespace Kaliba\Routing;
use RuntimeException;
use SplStack;
use SplDoublyLinkedList;
use Kaliba\Http\Request;
use Kaliba\Http\Middleware;


/**
 * Middle ware Stack
 *
 * This is an internal class that enables concentric middle ware layers. This
 * it is not visible to—and should not be used by—end users.
 */
final class Stack 
{
    /**
     * Middle ware call stack
     *
     * @var  \SplStack
     */
    private $stack;

    /**
     * Middleware stack lock
     *
     * @var bool
     */
    private $lock = false;
	
    /**
     * Request object
     *
     * @var Kaliba\Http\Request
     */
    private $request;
	
    /**
     * Create Middleware stack
     *
     */
    public function __construct(Request $request)
    {
        $this->request = $request;	
        $this->stack = new SplStack();
        $this->stack->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);
    }
	
    /**
     * Add middle ware
     *
     * This method prepends middle ware to the stack.
     *
     * @param Middleware $callable Any callable that accepts two arguments:
     *
     * @throws RuntimeException  If middle ware is added while the stack is dequeuing
     */
    public function add(Middleware $callable)
    {
        if($this->lock) {
            throw new RuntimeException('Middleware can’t be added once the stack is dequeuing');
        }
        if($this->stack->isEmpty() == false){
            $callable->set($this->stack->top());
        }     
        $this->stack[] = $callable;
        return $this;
    }
    
    /**
     * Add Many middle ware
     *
     * This method prepends  middle ware to the stack.
     *
     * @param array $callables Any callable that accepts two arguments:
     *
     * @throws RuntimeException         If middle ware is added while the stack is dequeuing
     */
    public function bulk(array $callables)
    {
        foreach ($callables as $callable) {
            $this->add($callable);
        }
    }

    /**
     * Call middle ware stack
     *
     * @return \Kaliba\Http\Response
     */
    public function run()
    {
        $middleware = $this->stack->top();
        $this->lock = true;
        $response = $middleware->handle($this->request);
        $this->lock = false;
        return $response;
    }
    
   
}
