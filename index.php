<?php

require_once 'include/init.php';
require_once 'include/form.php';

class PosterRequestForm extends Form
{
    protected $agenda = array();

    public function __construct(){
        $fname = 'poster-request';
        $this->name = $fname;
        $this->fields = array();

        if(get_cover_session()){
            $this->fields['activity'] = $this->get_activities_field();
            $this->fields['committee'] = $this->get_committees_field();
        }

        $this->fields['name']          = new TextField     ('name',           'Name',          $fname, false, array('placeholder'=> 'John Johnson or SomethingCee'));
        $this->fields['email']         = new EmailField    ('email',          'Email',         $fname, false, array('placeholder'=> 'myemailaddress@svcover.nl'));
        $this->fields['activity_name'] = new TextField     ('activity_name',  'Activity name', $fname, false, array('placeholder'=> 'Volcano zorbing'));
        $this->fields['date_time']     = new TextField     ('date-time',      'Date and time', $fname);
        $this->fields['location']      = new TextField     ('location',       'Location',      $fname, false, array('placeholder'=> 'Cover room'));
        $this->fields['description']   = new TextAreaField ('description',    'Description',   $fname, true,  array('placeholder'=> 'Sign up at volcanoes.svcover.nl'));
    }

    public function validate(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return false;

        $validate_select = function($select_field){
            return isset($select_field)
                && $select_field->validate()
                && !empty($select_field->value)
                && $select_field->value !== 'other';
        };

        $result = true;

        if ( $validate_select($this->fields['activity']) ){
            $activity = $this->agenda[ $this->fields['activity']->value ];

            if (empty($activity->locatie))
                $result = $this->fields['location']->validate()  && $result;
            $result = $this->fields['description']->validate()   && $result;
        } else if ($validate_select($this->fields['committee']) ){
            $result = $this->fields['activity_name']->validate() && $result;
            $result = $this->fields['date_time']->validate()     && $result;
            $result = $this->fields['location']->validate()      && $result;
            $result = $this->fields['description']->validate()   && $result;
        } else {
            $result = parent::validate();
        }
     
        return $result;
    }

    public function process_values(){
        $select_is_populated = function($select_field){
            return isset($select_field)
                && !empty($select_field->value)
                && $select_field->value !== 'other';
        };

        if ($select_is_populated($this->fields['activity'])){
            $activity = $this->agenda[ $this->fields['activity']->value ];

            $this->fields['name']->value = $activity->committee__naam;
            $this->fields['email']->value = get_committee_email($activity->committee__login);
            $this->fields['activity_name']->value = $activity->kop;
            $this->fields['date_time']->value = $activity->van;
            if (!empty($activity->locatie))
                $this->fields['location']->value = $activity->locatie;

        } else if ($select_is_populated($this->fields['committee'])){

            $this->fields['name']->value = $this->fields['committee']->get_selected_display();
            $this->fields['email']->value = get_committee_email($this->fields['committee']->value);
        }
    }

    protected function get_activities_field(){
        $agenda = cover_get_json('agenda', array('committee' => cover_session_get_committees()));

        $activities = array(array('Please choose your activity', array('disabled', 'selected')));

        foreach($agenda as $activity){
            $this->agenda[$activity->id] = $activity;
            $activities[$activity->id] = array(
                $activity->kop . ' - ' . $activity->committee__naam . ' - ' . date_format(date_create($activity->van), 'Y-m-d'), 
                array(
                    'value' => $activity->id,
                    'data-starttime' => $activity->van, 
                    'data-location' => $activity->locatie, 
                    'data-committee' => $activity->committee__login
                )
            );
        }
        
        $activities['other'] = array('other');
        
        return new SelectField('activity', 'Activity', $activities, $this->name, true); 
    }

    protected function get_committees_field(){
        $committees = array(array('Please choose your committee', array('disabled', 'selected')));
        
        foreach(get_cover_session()->committees as $committee => $display)
            $committees[$committee] = array($display);
        $committees['other'] = array('other');
        
        return new SelectField('committee', 'Committee', $committees, $this->name, true);
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
    protected $result = '';

    public function __construct(){
        $args = func_get_args();
        call_user_func_array(array($this, 'parent::__construct'), $args);
        $this->form = new PosterRequestForm();
    }

    public function run(){
        if (get_cover_session() && $this->form->validate()){
            $this->form->process_values();
            $this->send_email();
            $this->log_request();
            $this->success = true;
        }

        echo $this->render_layout();
    }

    protected function render_content(){
        if (!get_cover_session())
            $content = '<a href="<?= cover_login_url() ?>" class="btn btn-primary">Login and get started!</a>';
        else if (!empty($this->result)){
            $result = $this->result;
            $content = $this->render_template('templates/poster_request_form_processed.phtml', compact('result'));
        } else
            $content = $this->form->render(null, null, array('class' => 'btn btn-primary'));
        return $this->render_template('templates/poster_request_form.phtml', compact('content'));
    }

    protected function log_request(){
        $fp = fopen(SUBMISSION_LOG, 'a');
        fwrite($fp, "\n----------------------------------------------\n");
        fwrite($fp, sprintf("Poster request filed at %s\n", date(DATE_ATOM)));
        foreach ($this->form->fields as $name => $field)
            fwrite($fp, sprintf("%s: %s\n", $name, $field->value));
        fwrite($fp, sprintf("Result: %s\n", $this->result));
        fclose($fp);
    }

    protected function send_email(){
        $data = array();
        foreach ($this->form->fields as $name => $field)
            $data[$name] = $field->value;

        $data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        
        $data['email'] = 'martijnluinstra@gmail.com';

        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            sprintf('From: %s', EMAIL_SENDER),
            sprintf('Bcc: %s', EMAIL_SENDER)
        );

        $content =  $this->render_template('templates/email.phtml', $data);

        preg_match('{<title>(.+?)</title>}', $content, $subject);
        
        $success = mail(
            sprintf('%s <%s>', $data['name'], $data['email']),
            $subject[1], 
            $content, 
            implode("\r\n", $headers)
        );
        if ($success)
            $this->result = 'success';
        else
            $this->result = sprintf('Failed to send email!');
    }
}

$view = new HomepageView('Home', 'request');
$view->run();
