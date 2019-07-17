<?php
namespace Kaliba\View;
use Kaliba\Foundation\Application;
use Kaliba\ORM\Model;

abstract class WebPage implements Viewable
{
    use HasModel;

    /**
     * WebView constructor.
     * @param Model $model
     */
    public function __construct(Model $model=null)
    {
        $this->setModel($model);
    }

    /**
     * Errors to display on the page
     * @param mixed $errors
     * @return self
     */
    protected function errors($errors)
    {
        $this->getView()->errors((array)$errors);
        return $this;
    }

    /**
     * User Input to display on the page
     * @param array $input
     * @return self
     */
    protected function input($input)
    {
        $this->getView()->input($input);
        return $this;
    }

    /**
     * Render a template file
     * @param  string $template Filename of template file
     * @param  array $data data to be passed to the template
     *
     * @return string
     */
    protected function render($template, $data=[])
    {
        if(!empty($data)){
            $this->getView()->data($data);
        }
        $this->getView()->render($template);
    }

    /**
     * Get the Kaliba Application
     * @return Application
     */
    private function getApp()
    {
        return Application::getInstance();
    }

    /**
     * Get Viewer
     * @return Viewer
     */
    private function getView()
    {
        return $this->getApp()->get('view');
    }

}