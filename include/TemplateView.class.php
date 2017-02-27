<?php

abstract class TemplateView
{
    protected $title;

    public function __construct($title, $menu_id=''){
        $this->title = $title;
        $this->menu_id = $menu_id;
    }

    public function run(){
        echo $this->render_layout();
    }
    
    abstract protected function render_content();

    protected function get_menu_status($menu_id_cmp, $return_value='active'){
        return strtolower($this->menu_id) == strtolower($menu_id_cmp) ? $return_value : '';
    }

    protected function render_template(){
        ob_start();
        if (func_num_args() > 1)
            extract(func_get_arg(1));
        include func_get_arg(0);
        return ob_get_clean();
    }

    public function render_layout(){
        $title = $this->title;
        $content = $this->render_content();
        return $this->render_template('templates/layout.phtml', compact('title','content'));
    }

}
