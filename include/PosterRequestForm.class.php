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

        $this->fields['name']          = new TextField     ('name',           'Name',          $fname, false, array('placeholder' => 'John Johnson or SomethingCee'));
        $this->fields['email']         = new EmailField    ('email',          'Email',         $fname, false, array('placeholder' => 'myemailaddress@svcover.nl'));
        $this->fields['activity_name'] = new TextField     ('activity-name',  'Activity name', $fname, false, array('placeholder' => 'Volcano zorbing'));
        $this->fields['date_time']     = new TextField     ('date-time',      'Date and time', $fname, false, array('autocomplete' => 'off'));
        $this->fields['location']      = new TextField     ('location',       'Location',      $fname, false, array('placeholder' => 'Cover room'));
        $this->fields['description']   = new TextAreaField ('description',    'Description',   $fname, true,  array('placeholder' => 'Sign up at volcanoes.svcover.nl'));
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
        
        return new SelectField('activity', 'Activity', $activities, $this->name, true, array('hidden' => true)); 
    }

    protected function get_committees_field(){
        $committees = array(array('Please choose your committee', array('disabled', 'selected')));
        
        foreach(get_cover_session()->committees as $committee => $display)
            $committees[$committee] = array($display);
        $committees['other'] = array('other');
        
        return new SelectField('committee', 'Committee', $committees, $this->name, true, array('hidden' => true));
    }

    protected function render_body(){
        $body_html = array();
        
        foreach ($this->fields as $field) {
            $parent_attrs = array('class' => array());
            
            if (!empty($field->errors))
                $parent_attrs['class'][] = 'has-error';

            if (isset($field->attributes['hidden'])){
                if ($field->attributes['hidden'])
                    $parent_attrs['style'] = 'display: none;';
                unset($field->attributes['hidden']);
            }

            if (get_class($field) === 'CheckBoxField'){
                $parent_attrs['class'][] = 'checkbox';
                $body_html[] = $this->render_field(
                    $field,
                    array(), 
                    array('class' => 'help-block'), 
                    $parent_attrs
                );    
            } else {
                $parent_attrs['class'][] = 'form-group';
                $body_html[] = $this->render_field(
                    $field, 
                    array('class' => 'form-control'), 
                    array('class' => 'help-block'), 
                    $parent_attrs
                );    
            }
        }

        $body_html[] = '<button type="submit" class="btn btn-primary">Submit</button>';

        return implode(' ', $body_html);
    }

}
