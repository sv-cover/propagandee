<?php
require_once 'include/init.php';

class HomepageView extends TemplateView
{
    protected function render_content(){
        return 'Hoi!';
    }
}

$view = new HomepageView('Home');
$view->run();
