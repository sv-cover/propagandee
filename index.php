<?php

require_once 'include/init.php';
require_once 'include/PosterRequestForm.class.php';


/**
 * PosterRequestView: A class to handle poster requests
 */
class PosterRequestView extends TemplateView
{
    protected $form;
    protected $result = '';

    public function __construct(){
        $args = func_get_args();
        call_user_func_array(array($this, 'parent::__construct'), $args);
        $this->form = new PosterRequestForm();
    }

    /** Run the view */
    public function run(){
        if (cover_session_logged_in() && $this->form->validate()){
            // If member is logged in and form is validated, process it
            $this->form->process_values();
            $this->email_submission();
            $this->log_submission();
        }

        echo $this->render_layout();
    }

    /** Render the page content */
    protected function render_content(){
        if (!cover_session_logged_in())
            // Only display form if member is logged in
            $content = '<a href="<?= cover_login_url() ?>" class="btn btn-primary">Login and get started!</a>';
        else if (!empty($this->result)){
            // Render message if form is successfully processed
            $result = $this->result;
            $content = $this->render_template('templates/poster_request_form_processed.phtml', compact('result'));
        } else
            // Render Form
            $content = $this->form->render();

        return $this->render_template('templates/poster_request_form.phtml', compact('content'));
    }

    /** Send an email with the request to the applicant and the PubliciTee */
    protected function email_submission(){
        // Convert form to email data
        $data = array();
        foreach ($this->form->fields as $name => $field)
            $data[$name] = $field->value;

        // Sanitize email address
        $data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

        // Create email headers, BCC to PubliciTee
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            sprintf('From: %s', EMAIL_SENDER),
            sprintf('Bcc: %s', EMAIL_SENDER)
        );

        // Render email body
        $content = $this->render_template('templates/email.phtml', $data);

        // Fetch subject from rendered email body
        preg_match('{<title>(.+?)</title>}', $content, $subject);
        
        // Send email
        $success = mail(
            sprintf('%s <%s>', $data['name'], $data['email']),
            $subject[1], 
            $content, 
            implode("\r\n", $headers)
        );

        // Determine wether email has ben send succesfully
        if ($success)
            $this->result = 'success';
        else
            $this->result = sprintf('Failed to send email!');
    }

    /** Log the request in case shit hits the fan*/
    protected function log_submission(){
        $path = pathinfo(SUBMISSION_LOG);
        $filename = $path['dirname'] . DIRECTORY_SEPARATOR . $path['filename']. date('_Y_m.') . $path['extension'];
        $fp = fopen($filename, 'a');
        
        // Create log header
        fwrite($fp, "\n----------------------------------------------\n");
        fwrite($fp, sprintf("Poster request filed at %s\n", date(DATE_ATOM)));
        
        // Log all fields in the form
        foreach ($this->form->fields as $name => $field)
            fwrite($fp, sprintf("%s: %s\n", $name, $field->value));

        // Log email result
        fwrite($fp, sprintf("Result: %s\n", $this->result));
        fclose($fp);
    }
}

// Create and run poster request view
$view = new PosterRequestView('Home', 'request');
$view->run();
