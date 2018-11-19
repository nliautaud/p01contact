<?php
/**
 * p01-contact - A simple contact forms manager
 *
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 * @package p01contact
 */
namespace P01C;

require 'P01contact_Field.php';

class P01contactForm
{
    private $manager;

    private $id;
    private $status;
    private $targets;
    private $fields;
    public $lang;
    public $sent;

    /**
     * @param P01contact $P01contact
     * @param int $id the form id
     */
    public function __construct($P01contact)
    {
        static $id;
        $id++;

        $this->manager = $P01contact;

        $this->id = $id;
        $this->status = '';
        $this->targets = array();
        $this->fields = array();
    }

    /**
     * Find tag parameters, populate fields and targets.
     *
     * @param string $params the params
     */
    public function parseTag($params)
    {
        // assure formating
        $params = str_replace('&nbsp;', ' ', $params);
        $params = strip_tags(str_replace("\n", '', $params));
        $params = html_entity_decode($params, ENT_QUOTES, 'UTF-8');

        // explode
        $sep = $this->config('separator');
        $params = array_filter(explode($sep, $params));

        // emails
        foreach ($params as $id => $param) {
            $param = trim($param);
            if (filter_var($param, FILTER_VALIDATE_EMAIL)) {
                $this->addTarget($param);
                unset($params[$id]);
            }
        }
        // default params
        if (empty($params)) {
            $default = $this->config('default_params');
            $params = array_filter(explode($sep, $default));
        }
        // create fields
        foreach (array_values($params) as $id => $param) {
            $this->parseParam($id, trim($param));
        }
        // default email addresses
        $default_emails = $this->getValidEmails($this->config('default_email'));
        foreach ($default_emails as $email) {
            $this->addTarget($email);
        }
    }
    /**
     * Create a field by parsing a tag parameter
     *
     * Find emails and parameters, create and setup form object.
     * @param int $id the field id
     * @param string $param the param to parse
     */
    private function parseParam($id, $param)
    {
        $param_pattern = '`\s*([^ ,"=!]+)';     // type
        $param_pattern.= '\s*(!)?';             // required!
        $param_pattern.= '\s*(?:"([^"]*)")?';   // "title"
        $param_pattern.= '\s*(?:\(([^"]*)\))?'; // (description)
        $param_pattern.= '\s*(?:(=[><]?)?';     // =value, =>locked, =<placeholder
        $param_pattern.= '\s*(.*))?\s*`';       // value

        preg_match($param_pattern, $param, $param);
        list(, $type, $required, $title, $desc, $assign, $values) = $param;

        $field = new P01contactField($this, $id, $type);

        // values
        switch ($type) {
            case 'select':
            case 'radio':
            case 'checkbox':
                $field->value = explode('|', $values);
                $field->resetSelectedValues();
                break;
            case 'askcopy':
                // checkbox-like structure
                $field->value = array($this->lang('askcopy'));
                break;
            case 'password':
                // password value is required value
                $field->required = $values;
                break;
            default:
                if ($assign == '=<') {
                    $field->placeholder = $values;
                } else {
                    // simple value
                    $field->value = $values;
                }
        }
        // required
        if ($type != 'password') {
            $field->required = $required == '!';
        }
        if ($type == 'captcha') {
            $field->required = true;
        }
        $field->title = $title;
        $field->description = $desc;
        $field->locked = $assign == '=>';

        $this->addField($field);
    }

    /**
     * Update POSTed form and try to send mail
     *
     * Check posted data, update form data,
     * define fields errors and form status.
     * At least, if there is no errors, try to send mail.
     */
    public function post()
    {
        if (empty($_POST['p01-contact_form'])
         || $_POST['p01-contact_form']['id'] != $this->id ) {
            return;
        }

        // check token
        if (!$this->checkToken()) {
            $this->setStatus('sent_already');
            $this->setToken();
            $this->reset();
            return;
        }

        $posted = $_POST['p01-contact_fields'];
        
        // populate fields values and check errors
        $hasFieldsErrors = false;
        $fields = $this->getFields();
        foreach ($fields as $field) {
            if (!isset($posted[$field->id])) {
                continue;
            }
            $posted_val = $posted[$field->id];
            $field->setValue($posted_val);
            $hasFieldsErrors = !$field->validate() || $hasFieldsErrors;
        }

        // check errors and set status
        if ($this->config('disable')) {
            $this->setStatus('disable');
            return;
        }
        if (count($this->targets) == 0) {
            $this->setStatus('error_notarget');
            return;
        }
        if ($hasFieldsErrors || $this->checkSpam($posted) !== true) {
            return;
        }

        $this->sendMail();
        $this->setToken();
        $this->reset();
    }


    /*
     *  SECURITY
     */


    /**
     * Check if the honeypot field is untouched and if the time between this post,
     * the page load and previous posts and the hourly post count are valid
     * according to the settings, and set the form status accordingly.
     *
     * @param P01contact_form $form The submitted form
     * @param array $post Sanitized p01-contact data of $_POST
     * @return bool the result status
     */
    private function checkSpam($post)
    {
        if (isset($post['totally_legit'])) {
            $this->setStatus('error_honeypot');
            return false;
        }
        $loads = Session::get('pageloads');
        if (count($loads) > 1 && $loads[1] - $loads[0] < $this->config('min_sec_after_load')) {
            $this->setStatus('error_pageload');
            return false;
        }
        $lastpost = Session::get('lastpost', false);
        if ($lastpost && time() - $lastpost < $this->config('min_sec_between_posts')) {
            $this->setStatus('error_lastpost');
            return false;
        }
        $postcount = Session::get('postcount', 0);
        if (!$this->config('debug') && $postcount > $this->config('max_posts_by_hour')) {
            $this->setStatus('error_postcount');
            return false;
        }

        Session::set('lastpost', time());
        Session::set('postcount', $postcount + 1);

        return true;
    }

    /**
     * Create an unique hash in Session
     */
    private static function setToken()
    {
        Session::set('token', uniqid(md5(microtime()), true));
    }
    /**
     * Get the token in Session (create it if not exists)
     * @return string
     */
    public function getToken()
    {
        if (!Session::get('token', false)) {
            $this->setToken();
        }
        return Session::get('token');
    }
    /**
     * Compare the POSTed token to the Session one
     * @return boolean
     */
    private function checkToken()
    {
        return $this->getToken() === $_POST['p01-contact_form']['token'];
    }


    /*
     *  RENDER
     */


    /**
     * Return the html display of the form
     * @return string the <form>
     */
    public function html()
    {
        $html  = '<form action="'.PAGEURL.'#p01-contact'.$this->id.'" autocomplete="off" ';
        $html .= 'id="p01-contact' . $this->id . '" class="p01-contact" method="post">';

        if ($this->status) {
            $html .= $this->htmlStatus();
        }
        if (!$this->sent) {
            foreach ($this->fields as $field) {
                $html .= $field->html();
            }
            if ($this->config('use_honeypot')) {
                $html .= '<input type="checkbox" name="p01-contact_fields[totally_legit]" value="1" style="display:none !important" tabindex="-1" autocomplete="false">';
            }
            $html .= '<div><input name="p01-contact_form[id]" type="hidden" value="' . $this->id . '" />';
            $html .= '<input name="p01-contact_form[token]" type="hidden" value="' . $this->getToken() . '" />';
            $html .= '<input class="submit" type="submit" value="' . $this->lang('send') . '" /></div>';
        }
        $html .= '</form>';

        if ($this->config('debug')) {
            $html .= $this->debug(false);
        }
        return $html;
    }

    /**
     * Return an html display of the form status
     * @return string the <div>
     */
    private function htmlStatus()
    {
        $statusclass = $this->sent ? 'alert success' : 'alert failed';
        return '<div class="' . $statusclass . '">' . $this->lang($this->status) . '</div>';
    }

    /**
     * Return P01contact_form infos.
     * @return string
     */
    public function debug($set_infos)
    {
        $out = '<div class="debug debug_form">';
        static $post;
        if ($set_infos) {
            $post = $set_infos;
            return;
        }
        if ($post) {
            list($headers, $targets, $subject, $text_content, $html_content) = $post;
            $out.= '<h3>Virtually sent mail :</h3>';
            $out.= '<pre>'.htmlspecialchars($headers).'</pre>';
            $out.= "<pre>Targets: $targets\nSubject: $subject</pre>";
            $out.= "Text content : <pre>$text_content</pre>";
            $out.= "HTML content : <div style=\"border:1px solid #ccc;\">$html_content</div>";
        }
        $infos = $this;
        unset($infos->manager);
        $out .= "<h3>p01contact form $this->id :</h3>";
        $out .= preint($infos, true);
        $out .= '</div>';
        return $out;
    }

    /*
     *  MAIL
     */


    /**
     * Send a mail based on form
     *
     * Create the mail content and headers along to settings, form
     * and fields datas; and update the form status (sent|error).
     */
    public function sendMail()
    {
        $email = $name = $subject = $askcopy = null;
        $tpl_data = (object) null;
        $tpl_data->date = date('r');
        $tpl_data->ip = $_SERVER["REMOTE_ADDR"];
        $tpl_data->contact = $this->targets[0];
        // fields
        $tpl_data->fields = '';
        foreach ($this->fields as $field) {
            $tpl_data->fields .= $field->htmlMail();
            switch ($field->type) {
                case 'name':
                    $name = $field->value;
                    break;
                case 'email':
                    $email = $field->value;
                    break;
                case 'subject':
                    $subject = $field->value;
                    break;
            }
        }
        $html = $this->manager->renderTemplate('mail_template', $tpl_data);
        $text = strip_tags($html);

        if (empty($name)) {
            $name = $this->lang('anonymous');
        }
        if (empty($subject)) {
            $subject = $this->lang('nosubject');
        }

        // targets, subject, headers and multipart content
        $targets = implode(',', $this->targets);
        $encoded_subject = $this->encodeHeader($subject);

        $mime_boundary = '----'.md5(time());
        $headers = $this->mailHeaders($name, $email, $mime_boundary);

        $content = $this->mailContent($text, 'plain', $mime_boundary);
        $content .= $this->mailContent($html, 'html', $mime_boundary);
        $content .= "--$mime_boundary--\n\n";


        // debug
        if ($this->config('debug')) {
            $this->debug(array($headers, $targets, $subject, $text, $html));
            return $this->setStatus('sent_debug');
        }

        // send mail
        $success = mail($targets, $encoded_subject, $content, $headers);

        // log
        $this->manager->log(array(
            date('d/m/Y H:i:s'), $targets, $subject, $name, $success ? 'success':'error'
        ));

        if (!$success) {
            return $this->setStatus('error');
        }
        if (!$email || !$askcopy) {
            return $this->setStatus('sent');
        }

        // mail copy
        $copy = mail($email, $encoded_subject, $content, $headers);
        $this->setStatus($copy ? 'sent_copy' : 'sent_copy_error');
    }

    /**
     * Return the mail headers
     * @param string $name
     * @param string $email
     * @param string $mime_boundary
     * @return string
     */
    private function mailHeaders($name, $email, $mime_boundary)
    {
        $encoded_name = $this->encodeHeader($name);
        $headers  = "From: $encoded_name";
        if ($email) {
            $headers .= " <$email>\n";
            $headers .= "Reply-To: $encoded_name <$email>\n";
            $headers .= "Return-Path: $encoded_name <$email>";
        }
        $headers .= "\n";
        $headers .= "MIME-Version: 1.0\n";
        $headers .= "Content-type: multipart/alternative; boundary=\"$mime_boundary\"\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\n";
        return $headers;
    }

    /**
     * Return a multipart/alternative content part.
     * @param string $content
     * @param string $type the content type (plain, html)
     * @param string $mime_boundary
     * @return string
     */
    private function mailContent($content, $type, $mime_boundary)
    {
        $head = "--$mime_boundary\n";
        $head .= "Content-Type: text/$type; charset=UTF-8\n";
        $head .= "Content-Transfer-Encoding: 7bit\n\n";
        return $head.$content."\n";
    }

    /**
     * Format a string for UTF-8 email headers.
     * @param string $string
     * @return string
     */
    private function encodeHeader($string)
    {
        $string = base64_encode(html_entity_decode($string, ENT_COMPAT, 'UTF-8'));
        return "=?UTF-8?B?$string?=";
    }

    /**
     * Return array of valid emails from a comma separated string
     * @param string $emails
     * @return array
     */
    public static function getValidEmails($emails)
    {
        return array_filter(explode(',', $emails), function ($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }

    /**
     * GETTERS / SETTERS
     */

    /*
     * Reset all fields values and errors
     */
    public function reset()
    {
        foreach ($this->fields as $field) {
            $field->value = '';
            $field->error = '';
        }
    }
    public function getTargets()
    {
        return $this->targets;
    }
    public function addTarget($tget)
    {
        if (in_array($tget, $this->targets) === false) {
            $this->targets[] = $tget;
        }
    }
    public function getField($id)
    {
        return $this->fields[$id];
    }
    public function getFields()
    {
        return $this->fields;
    }
    public function addField($field)
    {
        $this->fields[] = $field;
    }
    public function getStatus()
    {
        return $this->status;
    }
    public function setStatus($status)
    {
        if (!is_string($status)) {
            return;
        }
        $this->status = $status;
        if (substr($status, 0, 4) == 'sent') {
            $this->sent = true;
        }
    }
    public function getId()
    {
        return $this->id;
    }
    public function config($key)
    {
        return $this->manager->config($key);
    }
    public function lang($key)
    {
        return $this->manager->lang($key, $this->lang);
    }
}
