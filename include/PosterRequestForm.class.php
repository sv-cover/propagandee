<?php

require_once 'include/init.php';
require_once 'include/form.php';


/**
 * PosterRequestForm: Renders and validates poster request form
 */
class PosterRequestForm extends Form
{
    protected $agenda = array();

    public function __construct(){
        $fname = 'poster-request';
        $this->name = $fname;
        $this->fields = array();

        // Set activity and committee field, but only if member is logged in
        if(cover_session_logged_in()){
            // Fetch agenda data from Cover API
            $agenda = cover_get_json('agenda', array('committee' => cover_session_get_committees()));
            if (!empty($agenda))
                $this->fields['activity'] = $this->create_activities_field($agenda);
            if (!empty(get_cover_session()->committees))
                $this->fields['committee'] = $this->create_committees_field(get_cover_session()->committees);
        }

        // Set other fields
        $this->fields['name']          = new StringField   ('name',           'Name',          $fname, false, array('placeholder' => 'John Johnson or SomethingCee'));
        $this->fields['email']         = new EmailField    ('email',          'Email',         $fname, false, array('placeholder' => 'myemailaddress@svcover.nl'));
        $this->fields['activity_name'] = new StringField   ('activity-name',  'Activity name', $fname, false, array('placeholder' => 'Volcano zorbing'));
        $this->fields['date_time']     = new StringField   ('date-time',      'Date and time', $fname, false, array('autocomplete' => 'off'));
        $this->fields['location']      = new StringField   ('location',       'Location',      $fname, false, array('placeholder' => 'Cover room'));
        $this->fields['description']   = new TextAreaField ('description',    'Description',   $fname, true,  array('placeholder' => 'Sign up at volcanoes.svcover.nl'));
    }

    /** Validate form */
    public function validate(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return false;

        // Helper function to know wheter select field is set
        $validate_select = function($select_field){
            return isset($select_field)
                && $select_field->validate()
                && !empty($select_field->value)
                && $select_field->value !== 'other';
        };

        $result = true;

        if ( $validate_select($this->fields['activity']) ){
            // Validate location and description if activity is selected
            $activity = $this->agenda[ $this->fields['activity']->value ];

            if (empty($activity->locatie))
                $result = $this->fields['location']->validate()  && $result;

            $result = $this->fields['description']->validate()   && $result;
        } else if ($validate_select($this->fields['committee']) ){
            // Validate activity_name, date_time, location and description if committee is selected
            $result = $this->fields['activity_name']->validate() && $result;
            $result = $this->fields['date_time']->validate()     && $result;
            $result = $this->fields['location']->validate()      && $result;
            $result = $this->fields['description']->validate()   && $result;
        } else {
            // Use default validation if nothing is selected
            $result = parent::validate();
        }
     
        return $result;
    }

    /** Process activity and committee details to deault fields */
    public function process_values(){
        // Helper function to know wheter select field is set
        $select_is_populated = function($select_field){
            return isset($select_field)
                && !empty($select_field->value)
                && $select_field->value !== 'other';
        };

        if ($select_is_populated($this->fields['activity'])){
            // Set name, email, activity_name, date_time and location based on selected activity
            $activity = $this->agenda[ $this->fields['activity']->value ];

            $this->fields['name']->value = $activity->committee__naam;
            $this->fields['email']->value = get_committee_email($activity->committee__login);
            $this->fields['activity_name']->value = $activity->kop;
            $this->fields['date_time']->value = $activity->van;
            if (!empty($activity->locatie))
                $this->fields['location']->value = $activity->locatie;

        } else if ($select_is_populated($this->fields['committee'])){
            // Set name and email based selected committee
            $this->fields['name']->value = $this->fields['committee']->get_selected_display();
            $this->fields['email']->value = get_committee_email($this->fields['committee']->value);
        }
    }

    /** Create activities field */
    protected function create_activities_field($agenda){
        // Set placeholder option
        $activities = array(array('Please choose your activity', array('disabled', 'selected')));

        // Create options for activities in the agenda and backup to global agenda for convenience
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
        
        // Create and return field
        return new SelectField('activity', 'Activity', $activities, $this->name, false, array('hidden' => true)); 
    }

    protected function create_committees_field($committees){
        // Set placeholder option
        $committee_options = array(array('Please choose your committee', array('disabled', 'selected')));
        
        // Create options for all committees the current logged in member is in
        foreach($committees as $committee => $display)
            $committee_options[$committee] = array($display);

        $committee_options['other'] = array('other');
        
        // Create and return field
        return new SelectField('committee', 'Committee', $committee_options, $this->name, false, array('hidden' => true));
    }

    /** Returns a bootstrap style HTML string of the body of the form */
    protected function render_body(){
        $body_html = array();
        
        foreach ($this->fields as $field) {
            $parent_attrs = array('class' => array());
            
            // Highlight field on error
            if (!empty($field->errors))
                $parent_attrs['class'][] = 'has-error';

            // Hide fields that depend on javascript 
            // (custom feature, complete form without committee and activity select is used as fallback)
            if (isset($field->attributes['hidden'])){
                if ($field->attributes['hidden'])
                    $parent_attrs['style'] = 'display: none;';
                unset($field->attributes['hidden']);
            }

            // Render field, have special treatement for checkboxes (which we don't have :S )
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

        // Add submit button :)
        $body_html[] = '<button type="submit" class="btn btn-primary">Submit</button>';

        return implode(' ', $body_html);
    }

}
