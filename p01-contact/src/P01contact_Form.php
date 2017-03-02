<?php
/**
 * p01-contact - A simple contact forms manager
 *
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 * @package p01-contact
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
        // assure encoding
        $params = str_replace('&nbsp;', ' ', $params);
        $params = html_entity_decode($params, ENT_QUOTES, 'UTF-8');

        // explode
        $sep = $this->config('separator');
        $params = array_filter(explode($sep, $params));

        // emails
        foreach ($params as $id => $param) {
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
        foreach ($params as $id => $param) {
            $this->parseParam($id, $param);
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

        $values_pattern = '`(?:^|\|)\s*(?:"([^"]+)")?\s*([^| ]+)?`';

        preg_match($param_pattern, $param, $param);
        list(, $type, $required, $title, $desc, $assign, $values) = $param;

        $field = new P01contactField($this, $id, $type);

        // values
        switch ($type) {
            case 'select':
            case 'radio':
            case 'checkbox':
                // fields with multiples values
                preg_match_all($values_pattern, $values, $values, PREG_SET_ORDER);
                $values = unset_r($values, 0);
                $field->value = $values;
                break;
            case 'askcopy':
                // checkbox-like structure
                $field->value = array(array(1 => $this->lang('askcopy')));
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

        $posted = $this->format($_POST['p01-contact_fields']);

        // check token and spam
        if (!$this->checkToken()) {
            $this->setStatus('sent_already');
            $this->setToken();
            $this->reset();
            return;
        }
        if (!$this->checkSpam($posted)) {
            return;
        }

        $fields = $this->getFields();
        foreach ($fields as $id => $field) {
            $posted_val = $posted[$field->id];
            $field->setValue($posted_val);
            $field->validate();
            if ($field->error) {
                $errors = true;
            }
        }

        if ($errors) {
            return;
        }

        if ($this->config('disable')) {
            return $this->setStatus('disable');
        }
        if (count($this->targets) == 0) {
            return $this->setStatus('target');
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
    public function checkSpam($post)
    {
        $s = $_SESSION['p01-contact'];
        if (!isset($s['first_post']) || time() - $s['first_post'] > 3600) {
            $s['first_post'] = time();
            $s['post_count'] = 0;
        }

        if (isset($post['totally_legit'])) {
            $this->setStatus('honeypot');
            return false;
        }
        if (time() - $s['last_page_load'] < $this->config('min_sec_after_load')) {
            $this->setStatus('wait_load');
            return false;
        }
        if (time() - $s['last_post'] < $this->config('min_sec_between_posts')) {
            $this->setStatus('sent_recently');
            return false;
        }
        if (!$this->config('debug') && $s['post_count'] > $this->config('max_posts_by_hour')) {
            $this->setStatus('wait_hour');
            return false;
        }

        $s['last_post'] = time();
        $s['post_count']++;

        $_SESSION['p01-contact'] = $s;

        return true;
    }

    /**
     * Create an unique hash in SESSION
     */
    private static function setToken()
    {
        $_SESSION['p01-contact']['token'] = uniqid(md5(microtime()), true);
    }
    /**
     * Get the token in SESSION (create it if not exists)
     * @return string
     */
    public function getToken()
    {
        if (!isset($_SESSION['p01-contact']['token'])) {
            $this->setToken();
        }
        return $_SESSION['p01-contact']['token'];
    }
    /**
     * Compare the POSTed token to the SESSION one
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
            foreach ($this->fields as $id => $field) {
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
            $html .= $this->debug();
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
     * Return an html link
     * @param string $href the link address
     * @param string $title if not used, the link title will be the address
     * @param string $protocol Default http://
     * @return string the <a>
     */
    private function htmlLink($href, $title = false, $protocol = 'http://')
    {
        return "<a href=\"$protocol$href\">".($title ? $title : $href).'</a>';
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
            $out.= "HTML content : <div style=\"border:1px solid #ccc;padding:15px;\">$html_content</div>";
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
        $body = '';
        $skip_in_message = array('name','email','subject','captcha');
        foreach ($this->fields as $field) {
            if (in_array($field->type, $skip_in_message) || empty($field->value)) {
                continue;
            }
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

            // field name
            $title = !empty($field->title) ? $field->title : $field->type;
            $body .= '<p><strong>' . $this->lang($field->title).'</strong> :';

            switch ($field->type) {
                case 'message':
                case 'textarea':
                    $body .= '<p style="margin:10px;padding:10px;border:1px solid silver">';
                    $body .= nl2br($field->value) . '</p>';
                    break;
                case 'url':
                    $body .= $this->htmlLink($field->value);
                    break;
                case 'checkbox':
                case 'select':
                case 'radio':
                    $body .= '<ul>';
                    foreach ($field->value as $v) {
                        if (isset($v[2]) && $v[2] == 'selected') {
                            $body .=  '<li>' . $v[1] . '</li>';
                        }
                    }
                    $body .= '</ul>';
                    break;
                case 'askcopy':
                    if (!in_array('selected', $field->value[0])) {
                        break;
                    }
                    $body .= '<p><strong>' . $this->lang('askedcopy').'.</strong></p>';
                    break;
                default:
                    $body .=  $field->value;
            }
            $body .= '</p>';
        }

        if (empty($name)) {
            $name = $this->lang('anonymous');
        }
        if (empty($subject)) {
            $subject = $this->lang('nosubject');
        }

        // header
        $head .= '<h2>' . $this->lang('fromsite') . ' <em>' . SERVER . '</em></h2>';
        $head .= '<h3>' . date('r') . '</h3>';
        $head .= "<p><strong>From :</strong> $name";
        if ($email) {
            $head .= " (<a href=\"mailto:$email\">$email</a>)";
        }
        $head .= '</p>';

        // footer
        $foot  = '<p><em>';
        $foot .= $this->lang('sentfrom') . ' ';
        $foot .= $this->htmlLink(PAGEURL, PAGEURI);
        $foot .= '<br>If this mail should not be for you, please contact ';
        $foot .= $this->htmlLink($this->targets[0], false, 'mailto:');
        $foot .= '</em></p>';

        if (extension_loaded('mbstring')) {
            $subject = mb_encode_mimeheader(html_entity_decode($subject, ENT_COMPAT, 'UTF-8'), 'UTF-8', 'Q');
            $name = mb_encode_mimeheader(html_entity_decode($name, ENT_COMPAT, 'UTF-8'), 'UTF-8', 'Q');
        }

        $mime_boundary = '----'.md5(time());

        $headers  = "From: $name";
        if ($email) {
            $headers .= " <$email>\n";
            $headers .= "Reply-To: $name <$email>\n";
            $headers .= "Return-Path: $name <$email>";
        }
        $headers .= "\n";
        $headers .= "MIME-Version: 1.0\n";
        $headers .= "Content-type: multipart/alternative; boundary=\"$mime_boundary\"\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\n";

        $html = $head . $body . $foot;
        $text = strip_tags(preg_replace('`<(/?p|br|h\d)[^>]*>`i', "\n", $html));

        //plain text version
        $content  = "--$mime_boundary\n";
        $content .= "Content-Type: text/plain; charset=UTF-8\n";
        $content .= "Content-Transfer-Encoding: 7bit\n";
        $content .= $text."\n";
        //html version
        $content .= "--$mime_boundary\n";
        $content .= "Content-Type: text/html; charset=UTF-8\n";
        $content .= "Content-Transfer-Encoding: 7bit\n\n";
        $content .= $html."\n\n";
        $content .= "--$mime_boundary--\n\n";

        $targets = implode(',', $this->targets);

        // debug
        if ($this->config('debug')) {
            $this->debug(array($headers, $targets, $subject, $text, $html));
            return $this->setStatus('sent_debug');
        }

        // send mail
        $success = mail($targets, $subject, $content, $headers);

        // log
        $this->manager->log(array(
            date('r'), $targets, $subject, $success ? 'success':'error'
        ));

        if (!$success) {
            return $this->setStatus('error');
        }
        if (!$email || !$askcopy) {
            return $this->setStatus('sent');
        }

        // mail copy
        $copy = mail($email, $subject, $content, $headers);
        $this->setStatus($copy ? 'sent_copy' : 'sent_copy_error');
    }

    /*
     * HELPERS
     */

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
     * Format values
     *
     * For aesthetic and security, and recursive.
     * @param array $val
     * @return array
     */
    private function format($val)
    {
        if (is_array($val)) {
            foreach ($val as $key => $v) {
                $val[$key] = $this->format($v);
            }
            return $val;
        }
        // mb_convert_encoding
        $val = htmlspecialchars_decode(utf8_decode(htmlentities($val, ENT_COMPAT, 'UTF-8', false)));
        return $val;
    }

    /**
     * GETTERS / SETTERS
     */

    /*
     * Reset all fields values and errors
     */
    public function reset()
    {
        foreach ($this->fields as $id => $field) {
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
