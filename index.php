<?php

require_once 'include/init.php';
require_once 'include/PosterRequestForm.class.php';


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
            $this->email_submission();
            $this->log_submission();
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
            $content = $this->form->render();
        return $this->render_template('templates/poster_request_form.phtml', compact('content'));
    }

    protected function email_submission(){
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

    protected function log_submission(){
        $fp = fopen(SUBMISSION_LOG, 'a');
        fwrite($fp, "\n----------------------------------------------\n");
        fwrite($fp, sprintf("Poster request filed at %s\n", date(DATE_ATOM)));
        foreach ($this->form->fields as $name => $field)
            fwrite($fp, sprintf("%s: %s\n", $name, $field->value));
        fwrite($fp, sprintf("Result: %s\n", $this->result));
        fclose($fp);
    }
}

$view = new HomepageView('Home', 'request');
$view->run();
