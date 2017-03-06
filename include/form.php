<?php

/** Returns HTML safe value */
function form_escape($value){
    return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
}


/** Renders HTML attributes from array */
function form_render_attributes($attributes){
    $attribute_html = array();

    foreach ($attributes as $key => $value){
        if (is_array($value))
            $value = implode(' ', $value);

        if (is_int($key))
            $attribute_html[] = form_escape($value);
        else
            $attribute_html[] = sprintf('%s="%s"', $key, form_escape($value));
    }

    return implode(' ', $attribute_html);
}


/**
 * Form: A generic class to render and validate an HTML form
 */
class Form
{
    protected $name;
    public $fields;

    public function __construct($name, $fields=array()){
        $this->name = $name;
        $this->fields = $fields;
    }

    /** Returns true if form is submitted and all fields are validated */
    public function validate(){
        if ($_SERVER['REQUEST_METHOD'] !== 'POST')
            return false;

        $result = true;
        foreach ($this->fields as $field)
            $result = $field->validate() && $result;

        return $result;
    }
    
    /** Returns HTML string with errors of a field */
    protected function render_field_errors($field, $attributes){
        $error_html = array();
        $errors = array_unique($field->errors);
        foreach ($errors as $error) {
            if ($error === true) 
                continue;

            $error_html[] = sprintf('<span %s>%s</span>', 
                form_render_attributes($attributes),
                form_escape($error));
        }
        return implode(' ', $error_html);
    }

    /** Returns HTML string of a field, with label and errors in a container element */
    protected function render_field($field, $attributes=array(), $error_attributes=array(), $parent_attributes=array()){
        if (get_class($field) === 'CheckBoxField')
            return sprintf('<div %s>%s %s</div>', 
                form_render_attributes($parent_attributes),
                $field->render_with_label($attributes),
                $this->render_field_errors($field, $error_attributes)
            );
        return sprintf('<div %s>%s %s %s</div>', 
            form_render_attributes($parent_attributes),
            $field->render_label(),
            $field->render($attributes),
            $this->render_field_errors($field, $error_attributes)
        );
    }
    
    /** Returns HTML string of a field by key, with label and errors in a container element */
    protected function render_field_by_key($key, $attributes=array(), $error_attributes=array(), $parent_attributes=array()){
        return $this->render_field($this->fields[$key], $attributes, $error_attributes, $parent_attributes);
    }

    /** Returns HTML string of the body of the form */
    protected function render_body(){
        $body_html = array();
        
        foreach ($this->fields as $field)
            $body_html[] = $this->render_field($field);

        $body_html[] = '<button type="submit">Submit</button>';

        return implode(' ', $body_html);
    }

    /** Returns HTML string of the form */
    public function render($attributes=array(), $action=null){
        $attributes['id'] = $this->name;
        $attributes['method'] = 'POST';

        if(!empty($action))
            $attributes['action'] = $action;

        return sprintf('<form %s>%s</form>',
            form_render_attributes($attributes),
            $this->render_body()
        );
    }
    
}


/**
 * Field: An abstract, generic class for a HTML field
 */
abstract class Field
{
    protected $name;
    protected $label;
    protected $form_name;
    public $optional;
    public $attributes;
    public $value;
    public $errors = array();

    public function __construct($name, $label, $form_name='', $optional=false, $attributes=array()){
        $this->name = $name;
        $this->label = $label;
        $this->form_name = $form_name;
        $this->optional = $optional;
        $this->attributes = $attributes;
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST[$this->name]))
            $this->value = $_POST[$this->name];
    }

    /** 
     * Returns true if field has a value or is optional, 
     * sets error and returns false otherwise 
     */
    public function validate(){
        if ($this->optional || ( isset($this->value) && !empty(trim($this->value)) ) )
            return true;
        $this->errors[] = sprintf('%s is required', $this->label);
        return false;
    }

    /** Returns HTML string of the label of the field */
    public function render_label(){
        return sprintf('<label for="%s">%s</label>', $this->name, $this->label);
    }

    /** Returns HTML string of the field */
    abstract public function render($attributes);
}


/**
 * InputField: An class for a HTML input field
 */
class InputField extends Field
{
    protected $type;

    public function __construct(){
        $args = func_get_args();
        $this->type = array_shift($args);
        call_user_func_array(array('parent', '__construct'), $args);
    }

    /** Returns HTML string of the field */
    public function render($attributes){
        $attributes = array_merge($this->attributes, $attributes);
        $attributes['type'] = $this->type;
        $attributes['name'] = $this->name;
        $attributes['id'] = $this->form_name . '-' . $this->name;

        if (isset($this->value) )
            $attributes['value'] = $this->value;

        return sprintf("<input %s>\n", form_render_attributes($attributes));
    }
}


/**
 * TextAreaField: An class for a HTML textarea field
 */
class TextAreaField extends Field
{
    /** Returns HTML string of the field */
    public function render($attributes){
        $attributes = array_merge($this->attributes, $attributes);
        $attributes['name'] = $this->name;
        $attributes['id'] = $this->form_name . '-' . $this->name;

        $value = isset($this->value) ? $this->value : '';

        return sprintf("<textarea %s>%s</textarea>\n",
            form_render_attributes($attributes),
            form_escape($value));
    }
}

/**
 * CheckBoxField: An class for a HTML input field with type="checkbox"
 */
class CheckBoxField extends Field
{
    public function __construct(){
        $args = func_get_args();
        call_user_func_array(array('parent', '__construct'), $args);
    }

    /** 
     * Returns true if field has a value or is optional, 
     * sets error and returns false otherwise 
     */
    public function validate(){
        if ($this->optional || !empty($this->value))
            return true;
        $this->errors[] = true;
        return false;
    }

    /** Returns HTML string of the field and its label */
    public function render_with_label($attributes){
        return sprintf('<label>%s %s</label>', $this->render($attributes), $this->label);
    }

    /** Returns HTML string of the field */
    public function render($attributes){
        $attributes = array_merge($this->attributes, $attributes);
        $attributes['type'] = 'checkbox';
        $attributes['name'] = $this->name;
        $attributes['id'] = $this->form_name . '-' . $this->name;

        if (!empty($this->value))
            $attributes[] = 'checked';

        return sprintf("<input %s>", form_render_attributes($attributes));
    }
}


/**
 * CheckBoxField: An class for a HTML select field
 */
class SelectField extends Field
{   
    protected $options;

    public function __construct($name, $label, $options, $form_name='', $optional=false, $attributes=array()){
        $this->options = $options;
        parent::__construct($name, $label, $form_name, $optional, $attributes);
    }

    /** 
     * Returns true if field has a value that is a valid option or if the field is optional,
     * sets error and returns false otherwise 
     */
    public function validate(){
        $value = isset($this->value) ? $this->value : '';
        
        if ($this->optional && $value === '')
            return true;
        else if (array_key_exists($value, $this->options))
            return true;

        if ($value === '' )
            $this->errors[] = sprintf('%s is required', $this->label);
        else 
            $this->errors[] = sprintf('Please select one of the available options');

        return false;
    }

    /** Returns HTML string of the field */
    public function render($attributes){
        $attributes = array_merge($this->attributes, $attributes);
        $attributes['name'] = $this->name;
        $attributes['id'] = $this->form_name . '-' . $this->name;

        $options_html = array();

        foreach ($this->options as $value => $option){
            $option_attributes = !empty($option[1]) ? $option[1] : array();

            if (!is_int($value))
                $option_attributes['value'] = $value;

            if (isset($this->value) && $this->value == $value)
                $option_attributes[] = 'selected';

            $options_html[] = sprintf("\t<option %s>%s</option>",
                form_render_attributes($option_attributes),
                form_escape($option[0]));
        }

        return sprintf("<select %s>\n%s</select>\n",
            form_render_attributes($attributes),
            implode("\n", $options_html));
    }

    /** Returns option for name */
    public function get_option($name){
        return $this->options[$name];
    }

    /** Returns display value of the selected option */
    public function get_selected_display(){
        return $this->get_option($this->value)[0];
    }
}


/**
 * StringField: An class for a HTML input field with type="text"
 * (named StringField instead of TextField to prevent confusion with TextAreaField)
 */
class StringField extends InputField
{
    public function __construct(){
        $args = func_get_args();
        array_unshift($args, 'text');
        call_user_func_array(array('parent', '__construct'), $args);
    }
}


/**
 * EmailField: An class for a HTML input field with type="email"
 */
class EmailField extends InputField
{
    public function __construct(){
        $args = func_get_args();
        array_unshift($args, 'email');
        call_user_func_array(array('parent', '__construct'), $args);
    }

    /** 
     * Returns true if field has a value that is a valid emailaddress or if the field is optional,
     * sets error and returns false otherwise 
     */
    public function validate(){
        $value = isset($this->value) ? $this->value : '';
        $value = filter_var($value, FILTER_SANITIZE_EMAIL);

        if ($this->optional && $value === '')
            return true;
        else if (filter_var($value, FILTER_VALIDATE_EMAIL))
            return true;

        if ($value === '' )
            $this->errors[] = sprintf('%s is required', $this->label);
        else 
            $this->errors[] = sprintf('Please enter a valid email address');

        return false;
    }
}
