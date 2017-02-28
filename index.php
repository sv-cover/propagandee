<?php

require_once 'include/init.php';
require_once 'include/form.php';

class PosterRequestForm extends Form
{
    public function __construct(){
        $fname = 'poster-request';
        $this->name = $fname;
        $this->fields = array(
            'name'          => new TextField     ('name',           'Name',          $fname, false, array('placeholder'=> 'John Johnson or SomethingCee')),
            'email'         => new EmailField    ('email',          'Email',         $fname, true,  array('placeholder'=> 'myemailaddress@svcover.nl')),
            'activity_name' => new TextField     ('activity_name',  'Activity name', $fname, false, array('placeholder'=> 'Volcano zorbing')),
            'date_time'     => new TextField     ('date-time',      'Date and time', $fname),
            'location'      => new TextField     ('location',       'Location',      $fname, false, array('placeholder'=> 'Cover room')),
            'description'   => new TextAreaField ('description',    'Description',   $fname, true,  array('placeholder'=> 'Sign up at volcanoes.svcover.nl'))
        );
    }

    public function validate(){
        $result = parent::validate();
        
        if (!$result)
            return false;

        foreach ($this->field as $field){
            if (!$field->optional && empty(trim($field->value))){
                $result = false;
                $field->errors[] = sprintf('%s should not be empty', $fields->label);
            }
        }

        return $result;
    }

    protected function render_field($field, $attributes=array(), $error_attributes=array()){
        $error_class = '';
        if (!empty($field->errors))
            $error_class = 'has-error';

        if (get_class($field) === 'CheckBoxField')
            return sprintf('<div class="checkbox %s">%s %s</div>', 
                $error_class,
                $field->render_with_label($attributes),
                $this->render_field_errors($field, array('class' => 'help-block')));

        $attributes['class'] = 'form-control';
        return sprintf('<div class="form-group %s">%s %s %s</div>', 
            $error_class,
            $field->render_label(),
            $field->render($attributes),
            $this->render_field_errors($field, array('class' => 'help-block')));
    }

}

class HomepageView extends TemplateView
{
    protected $form;

    public function __construct(){
        $args = func_get_args();
        call_user_func_array(array($this, 'parent::__construct'), $args);
        $this->form = new PosterRequestForm();
    }

    protected function render_content(){
        return $this->form->render(null, null, array('class' => 'btn btn-primary'));
    }
}

$view = new HomepageView('Home', 'request');
$view->run();
