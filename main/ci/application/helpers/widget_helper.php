<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

class Widget {

   public function run($name) {
        $widgetClass = APPPATH . 'widgets/'  . $name . 'Widget'.'.php';
        if (defined('TEMPLATE'))
            if (file_exists(APPPATH . 'widgets/' . TEMPLATE . '/' . $name . 'Widget'  . '.php'))
                $widgetClass = APPPATH . 'widgets/' . TEMPLATE . '/' . $name . 'Widget'  . '.php';

        if (!file_exists($widgetClass))
            return '<span style="color: red;">WIDGET ERROR: Cannot find `' . APPPATH . 'widgets/' . $name . 'Widget' . '.php' . '`</span>';

        $args = func_get_args();

        require_once $widgetClass;
        $name = ucfirst($name);

        $widget = new $name();
        return call_user_func_array(array($widget, 'run'), array_slice($args, 1));
    }

    function render($viewName, $data = array()) {
        $widgetView = APPPATH . 'widgets/views/' . $viewName . '.php';
        if (defined('TEMPLATE'))
            if (file_exists(APPPATH . 'widgets/' . TEMPLATE . '/views/' . $viewName  . '.php'))
                $widgetView = APPPATH . 'widgets/' . TEMPLATE . '/views/' . $viewName  . '.php';

        if (!file_exists($widgetView))
            return '<span style="color: red;">WIDGET ERROR: Cannot find `' . APPPATH . 'widgets/views/' . $viewName . '.php' . '`</span>';

        extract($data); 
        include $widgetView;
    }

    function load($object) {
        $this->$object =& load_class(ucfirst($object));
    }

    function __get($var) {
        static $ci;
        isset($ci) OR $ci =& get_instance();
        return $ci->$var;
    }
}