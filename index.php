<?php

require_once 'include/init.php';
require_once 'include/form.php';

class PosterRequestForm extends Form
{
    public function __construct(){
        $fname = 'poster-request';
        $this->name = $fname;
        $this->fields = array();
        if(get_cover_session()){
            $data = array(
                'method' => 'agenda',
                'session_id' => $_COOKIE[COVER_COOKIE_NAME], 
                'committees' => cover_session_get_committees()
            );
            $agenda = http_get_json(COVER_API_URL, $data);

            $activities = array(array('Please choose your activity', array('disabled', 'selected')));

            foreach($agenda as $activity)
                $activities[$activity->id] = array(
                    $activity->kop . ' - ' . $activity->committee__naam . ' - ' . date_format(date_create($activity->van), 'Y-m-d'), 
                    array(
                        'data-starttime' => $activity->van, 
                        'data-location' => $activity->locatie, 
                        'data-committee' => $activity->committee__login
                    )
                );
            $activities['other'] = array('other');
            $this->fields['activity'] = new SelectField   ('activity',      'Activity',     $activities, $fname, true);

            $committees = array(array('Please choose your committee', array('disabled', 'selected')));
            foreach(get_cover_session()->committees as $committee => $display)
                $committees[$committee] = array($display);
            $committees['other'] = array('other');
            $this->fields['committee'] = new SelectField   ('committee',      'Committee',     $committees, $fname, true);
        }

        $this->fields['name']          = new TextField     ('name',           'Name',          $fname, false, array('placeholder'=> 'John Johnson or SomethingCee'));
        $this->fields['email']         = new EmailField    ('email',          'Email',         $fname, true,  array('placeholder'=> 'myemailaddress@svcover.nl'));
        $this->fields['activity_name'] = new TextField     ('activity_name',  'Activity name', $fname, false, array('placeholder'=> 'Volcano zorbing'));
        $this->fields['date_time']     = new TextField     ('date-time',      'Date and time', $fname);
        $this->fields['location']      = new TextField     ('location',       'Location',      $fname, false, array('placeholder'=> 'Cover room'));
        $this->fields['description']   = new TextAreaField ('description',    'Description',   $fname, true,  array('placeholder'=> 'Sign up at volcanoes.svcover.nl'));
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
        if ($this->form->validate())
            $form = null;
        else
            $form = $this->form->render(null, null, array('class' => 'btn btn-primary'));
        return $this->render_template('templates/poster_request_form.phtml', compact('form'));
    }
}

$view = new HomepageView('Home', 'request');
$view->run();
