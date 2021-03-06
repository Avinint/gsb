<?php

namespace Core\View;

class View implements \ArrayAccess
{
    protected $viewpath;
    protected $helpers = array();
    protected $app;
    protected $container;

    public function __construct($view, array $helpers = array())
    {

        $view = explode(':', $view);
		$module = array_shift($view);
        $this->view = implode(D_S, $view);
		$this->viewpath = ROOT.D_S.'App'.D_S.$module.D_S.'View'.D_S;
        $app = \App::getInstance();
        $this->container = $app->getContainer();
    }

    public function getRouter()
    {
        return $this->container['router'];
    }

    public function asset($path)
    {
        return $this->getRouter()->getAsset($path);
    }

    public function url($route)
    {
        return $this->getRouter()->generateURL($route);
    }

    public function display($file)
    {
        $file = str_replace (':', D_S, $file);
        include $this->viewpath.$file.'.php';
    }

    public function header()
    {
        include $this->viewpath.'Template'.D_S.'header.php';
    }

    public function footer()
    {
        include $this->viewpath.'Template'.D_S.'footer.php';
    }

    public function render($parameters, $template ='default')
    {
        $this->template = $template;
        ob_start();
        extract($parameters);
        require $this->viewpath.$this->view;
        $content = ob_get_clean();
        require $this->viewpath.'Template/'.$template.'.php';
    }

    public function tag($html, $tag = 'div', $attr = [], $parent = null)
    {
        // Pour chaque attribut on rajoute le html, exemple: class="", et on les combine
        $attributes = [];
        if($attr){
            foreach ($attr as $k => $v) {
                $attributes[$k] = empty($attr[$k])? '': ' '.$k.'="'.$v.'"';
            }
            $attributes = implode(' ', $attributes);
        }else{
            $attributes ='';
        }

        $result = '<'.$tag.$attributes.'>'.$html.'</'.$tag.'>';
        $result = $this->addParentTag($result, $parent);

        return $result;
    }

    public function addParentTag($html, $parent)
    {
        if($parent !== null){
            if(is_array($parent)){
                $attr = array();
                $attr['class'] = $parent[key($parent)];
                $parent = key($parent);
            }else{
                $attr = null;
            }
            $html = $this->tag($html, $parent, $attr);
        }
        return $html;
    }


    public function excerpt($excerpt, $button, $text = '', $maxLength = null)
    {

        $excerpt = $this->shorten($excerpt, $maxLength);
        if(empty($text)){
            $text = 'Plus...';
        }
        $excerpt  .= '...<form method ="post" action="'.$button.'"><input type="submit" value="'.$text.'"></form>';

        return $excerpt;
    }

    public function shorten($excerpt, $maxLength = null)
    {
        $maxLength = $maxLength === null? 100: $maxLength;
        if(strlen($excerpt) > $maxLength) {
            $excerpt   = substr($excerpt, 0, $maxLength-3);
            $lastSpace = strrpos($excerpt, ' ');
            $excerpt   = substr($excerpt, 0, $lastSpace);
        }

        return $excerpt;
    }

    public function getPath()
    {
        return $this->container['viewPath'];
    }

    public function offsetGet($name)
    {
        return $this->get($name);
    }

    public function offsetUnset($name)
    {
        throw new \Exception('Helpers cannot be offset');
    }

    public function offsetExists($name)
    {
        return isset($this->helpers[$name]);
    }

    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    public function set(Helper $helper)
    {
        $this->helpers[$helper->getName()] = $helper;

    }

    public function get($name)
    {
        if (!isset($this->helpers[$name])) {
            throw new \InvalidArgumentException(sprintf('The helper "%s" is not defined.', $name));
        }

        return $this->helpers[$name];
    }

    public function addHelpers(array $helpers)
    {
        foreach ($helpers as $alias => $helper) {
            $this->set($helper, is_int($alias) ? null : $alias);
        }
    }

    public function getContainer($service)
    {
        $app = App::getInstance();
        $container = $app->getContainer();

        return $container[$service];
    }
}