<?php

/**
 * CachedPoster: An abstract class to manage a view based on the default layout template
 */
abstract class TemplateView
{
    protected $title;
    protected $page_id;

    public function __construct($title, $page_id=''){
        $this->title = $title;
        $this->page_id = $page_id;
    }

    /** Run the view */
    public function run(){
        echo $this->render_layout();
    }
    
    /** Render the page content */
    abstract protected function render_content();

    /** 
     * Render a template with variables:
     * arg1: (relative) path to the template
     * arg2: array of key, value pairs of template variables
     */
    protected function render_template(){
        ob_start();
        if (func_num_args() > 1)
            extract(func_get_arg(1));
        include func_get_arg(0);
        return ob_get_clean();
    }

    /** Render the entire page */
    public function render_layout(){
        $title = $this->title;
        $page_id = $this->page_id;
        $content = $this->render_content();
        return $this->render_template('templates/layout.phtml', compact('title', 'page_id', 'content'));
    }
}
