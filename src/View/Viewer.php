<?php


namespace Kaliba\View;

use eftec\bladeone\BladeOne;
use Kaliba\Collection\Collection;
use Kaliba\Support\Arraybag;

class Viewer
{
    /**
     * @var BladeOne
     */
    protected $blade;

    /**
     *
     * @var ViewBag
     */
    protected $viewBag;

    /**
     * Viewer constructor.
     * @param BladeOne $blade
     */
    public function __construct(BladeOne $blade)
    {
        $this->viewBag = new ViewBag();
        $this->blade = $blade;
    }

    /**
     * Set Data to be rendered.
     * @param  string|array $key   Name of variable to assign or Array of keys => values
     * @param  mixed $value Value to assign to variable or NULL if $key is an Array
     * @return self
     */
    public function data($key, $value=null)
    {
        if($key instanceof Collection){
            $this->viewBag->add($key);
        }else{
            $this->viewBag->add($key, $value);
        }
        return $this;
    }

    /**
     * Errors to display on the page
     * @param array|string $errors
     * @return self
     */
    public function errors( $errors)
    {
        $errors = new Collection(Arraybag::flatten( (array)$errors ) );
        $this->data(compact('errors'));
        return $this;
    }

    /**
     * User Input to display on the page
     * @param array $data
     * @return self
     */
    public function input(array $data)
    {
        $input = new \stdClass();
        foreach ($data as $key => $value){
            $input->$key = $value;
        }
        $this->data(compact('input'));
        return $this;
    }

    /**
     * Get contents of template file
     * @param  string $template Filename of template file
     * @param  array $data data to be passed to the template
     *
     * @return string
     */
    public function content($template, $data=[])
    {
        if(!empty($data)){
            $this->data($data);
        }
        return $this->blade->run($template, $this->viewBag->all());

    }

    /**
     * Render a template file
     * @param  string $template Filename of template file
     * @param  array $data data to be passed to the template
     *
     * @return string
     */
    public function render($template, $data=[])
    {
        $content = $this->content($template, $data);
        print($content);

    }



}