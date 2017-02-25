<?php

abstract class TemplateView{
    protected $title;

    public function __construct($title){
        $this->title = $title;
    }

    public function run(){
        echo $this->render_layout();
    }
    
    abstract protected function render_content();

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
