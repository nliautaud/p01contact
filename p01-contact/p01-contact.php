<?php
/**
 * p01-contact - A simple contact forms manager
 *
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 * @package p01-contact
 * @version 1.0.0
 */
if(session_id()=='') session_start();

class P01contact
{
    public $version;
    public $default_email;
    public $default_lang;
    private $forms;
    private $config;
    private $first;

    public function __construct()
    {
        $this->version = '1.0.0';
        $this->forms = array();
        $dir = dirname(__FILE__);
        define('ROOTDIR', $dir);
        define('LANGPATH', ROOTDIR.'/lang/');
        define('CONFIGFILE', ROOTDIR.'/config.json');

        define('REPOURL', 'https://github.com/nliautaud/p01contact');
        define('WIKIURL', 'https://github.com/nliautaud/p01contact/wiki');
        define('ISSUESURL', 'https://github.com/nliautaud/p01contact/issues');
        define('APILATEST', 'https://api.github.com/repos/nliautaud/p01contact/releases/latest');

        $this->load_config();
    }

    /**
     * Query the releases API and return the new release infos, if there is one.
     *
     * @see https://developer.github.com/v3/repos/releases/#get-the-latest-release
     * @return object the release infos
     */
    function get_new_release() {
        $options  = array('http' => array('user_agent'=> $_SERVER['HTTP_USER_AGENT']));
        $context  = stream_context_create($options);
        $resp = file_get_contents(APILATEST, false, $context);
        if(!$resp) return;
        $resp = json_decode($resp);
        if(!$resp->name) return;
        if(version_compare($this->version, $resp->name) > 0)
            return $resp;
        return;
    }

    /**
     * Parse a string to replace tags by forms
     *
     * Find tags, create forms structures, check POST and modify string.
     * @param string $contents the string to parse
     * @return string the modified string
     */
    public function parse($contents)
    {
        $pattern = '`(?<!<code>)\(%\s*contact\s*(\w*)\s*:?\s*(.*)\s*%\)`';
        preg_match_all($pattern, $contents, $tags, PREG_SET_ORDER);
        $ids = array();

        // create forms structures from TAG
        foreach($tags as $tag) {
            $id = $this->new_form_id();
            $form = $this->parse_tag($id, $tag[2]);
            $form->lang = $tag[1];
            $this->forms[$id] = $form;
            $ids[] = $id; // forms manipulated by this parsing session
        }
        // modify forms structures from POST, send mail
        if(!empty($_POST['p01-contact_form'])) {
            $this->post();
        }
        // replace tags by forms
        foreach($ids as $id) {
            $contents = preg_replace($pattern, $this->forms[$id]->html(), $contents, 1);
        }

        if(count($this->forms) > 1)  return $contents;

        // styles and scripts
        $inc = '<style>'.file_get_contents(ROOTDIR.'/style.css').'</style>';
        $inc.= '<script>'.file_get_contents(ROOTDIR.'/scripts.js').'</script>';
        return $inc.$contents;
    }

    private function format($str)
    {
        $str = trim(preg_replace(
            array('`&nbsp;`','`&quot;`'),
            array(' ','"'),
            $str)
        );
        return $str;
    }
    /**
     * Create a form by parsing a tag
     *
     * Find emails and parameters, create and setup form object.
     * @param int $id the form id
     * @param string $tag the tag to parse
     * @return P01contact_form the form object
     */
    private function parse_tag($id, $tag)
    {
        $form = new P01contact_form($this, $id);
        $tag = $this->format($tag);
        $sep = $this->config('separator');
        $params = array_filter(explode($sep, $tag));

        // emails
        foreach($params as $id => $param) {
            if(filter_var($param, FILTER_VALIDATE_EMAIL)) {
                $form->add_target($param);
                unset($params[$id]);
            }
        }
        // default params
        if(empty($params)) {
            $default = $this->config('default_params');
            $default = $this->format($default);
            $params = array_filter(explode(',', $default));
        }
        // create fields
        foreach($params as $id => $param) {
            $field = $this->parse_tag_param($form, $id, $param);
        }
        // default email addresses
        $default_emails = $this->get_valid_emails($this->config('default_email'));
        foreach ($default_emails as $email) {
            $form->add_target($email);
        }

        return $form;
    }
    /**
     * Create a field by parsing a tag parameter
     *
     * Find emails and parameters, create and setup form object.
     * @param int $id the field id
     * @param string $tag the param to parse
     * @return P01contact_field the field object
     */
    private function parse_tag_param($form, $id, $param)
    {
        $param_pattern = '`\s*([^ ,"=!]+)';     // type
        $param_pattern.= '\s*(!)?';             // required
        $param_pattern.= '\s*("([^"]*)")?';     // title
        $param_pattern.= '\s*(\(([^"]*)\))?';   // description
        $param_pattern.= '\s*((=&gt;|=)?';      // assign
        $param_pattern.= '\s*([^,]*))?\s*`';    // values

        $values_pattern = '`(?:^|\|)\s*(?:"([^"]+)")?\s*([^| ]+)?`';

        preg_match($param_pattern, $param, $param);
        list(, $type, $required, , $title, , $desc, , $assign, $values) = $param;

        $field = new P01contact_field($form, $id, $type);

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
                // simple value
                $field->value = $values;
        }
        // required
        if($type != 'password') $field->required = $required == '!';
        if($type == 'captcha') $field->required = true;

        $field->title = $title;
        $field->description = $desc;
        $field->locked = $assign == '=&gt;';

        $form->add_field($field);
        return $field;
    }

    /**
     * Update POSTed form and try to send mail
     *
     * Check posted data, update form data,
     * define fields errors and form status.
     * At least, if there is no errors, try to send mail.
     */
    private function post()
    {
        // check posted data and update form data
        $form_id = $_POST['p01-contact_form']['id'];
        if(isset($this->forms[$form_id]))
        {
            $form = $this->forms[$form_id];
            $fields = $form->get_fields();
            $posted = $this->format_data($_POST['p01-contact_fields']);

            foreach($fields as $id => $field)
            {
                $field_post = $posted[$field->id];

                if($field->type == 'captcha')
                    $field_post = $_POST['g-recaptcha-response'];

                // for multiple-values fields, posted value define selection
                $value = $field->value;
                if(is_array($value)) {
                    // selections need to be an array
                    if(!is_array($field_post)) $selections = array($field_post);
                    else $selections = $field_post;
                    // reset value selection
                    foreach($value as $key => $val) {
                        $value[$key][2] = '';
                    }
                    // set value selection from POST
                    foreach($selections as $selection) {
                        foreach($value as $key => $val) {
                            if(trim($val[1]) == trim($selection))
                                $value[$key][2] = 'selected';
                        }
                    }
                    $field->value = $value;
                }
                // for unique value fields, posted value define value
                else $field->value = $field_post;

                $check = $field->check_content();
                $field->error = $check;
                if($check) $errors = True;
            }
            // SECURITY : check tokens
            if(!$this->config('debug') && !$this->check_token()) {
                $form->set_status('token');
                $this->set_token();
                $form->reset();
            }
            // try to send mail
            elseif(!isset($errors)) {
                if($this->config('disable')) {
                    $form->set_status('disable');
                } elseif($form->count_targets() == 0) {
                    $form->set_status('target');
                } else {
                    $form->send_mail();
                    $this->set_token();
                    $form->reset();
                }
            }
         }
    }

    /**
     * Return next accessible form ID
     * @param string $key the setting key
     * @return mixed the setting value
     */
    private function new_form_id()
    {
        end($this->forms);
        $id = key($this->forms) + 1;
        reset($this->forms);
        return $id;
    }

    /**
     * Print POST and p01-contact content.
     */
    public function debug()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $health = 'PHP version : '.phpversion()."\n";
        $health.= 'PHP mbstring (UTF-8) : '.(function_exists('mb_convert_encoding') ? 'OK' : 'MISSING');

        echo'<h2 style="color:#c33">p01-contact debug</h2>';
        echo'<h3>Health :</h3>';
        preint($health);
        if(!empty($_POST)) {
            echo'<h3>$_POST :</h3>';
            preint($_POST);
        }
        echo'<h3>$p01contact :</h3>';
        preint($this);
    }


    /**
     * Format array values
     *
     * For aesthetic and security, and recursive.
     * @param array $array
     * @return array
     */
    private function format_data($val)
    {
        if(is_array($val)) {
            foreach($val as $key => $v)
                $val[$key] = $this->format_data($v);
            return $val;
        }
        if(function_exists('mb_convert_encoding'))
            $val = mb_convert_encoding($val, 'UTF-8', 'UTF-8');
        $val = htmlentities($val, ENT_QUOTES, 'UTF-8');
        return $val;
    }

    /**
     * Return a traduction of the keyword
     *
     * Manage languages between requested langs and existing traductions.
     * @param string $key the keyword
     * @return string
     */
    public function lang($key, $lang = null)
    {
        global $p01contact_lang;

        if(!$lang) {
            $lang = $this->config('lang');
            $lang = empty($lang) ? $this->default_lang : $lang;
        }

        $path = LANGPATH . $lang . '.php';

        $lang = file_exists($path) ? $lang : 'en';

        include_once $path;

        if(isset($p01contact_lang[$key])) {
            return $p01contact_lang[$key];
        } else {
            return ucfirst($key);
        }
    }
    /**
     * Return list of existing langs from lang/langs.php
     * @return array
     */
    private function langs()
    {
        require LANGPATH . '/langs.php';
        return $p01contact_langs;
    }


    /*
     *  CONFIG
     */


    /**
     * Load the configuration file.
     */
    private function load_config()
    {
        $this->config = json_decode(@file_get_contents(CONFIGFILE));
        $this->default_config();
    }
    /**
     * Set the obligatory missing settings.
     */
    function default_config() {
        $default = array(
            'default_params' => 'name!, email!, subject!, message!',
            'separator' => ','
        );
        foreach ($default as $key => $value) {
            if(empty($this->config->{$key}))
                $this->config->{$key} = $value;
        }
    }
    /**
     * Update the configuration file with new data.
     *
     * @param string $file_path the config file path
     * @param array $new_values the new values to write
     * @param array $old_values the values to change
     * @return boolean file edition sucess
     */
    private function update_config($new_values)
    {
        if ($file = fopen(CONFIGFILE, 'w')) {
            fwrite($file, json_encode($new_values, JSON_PRETTY_PRINT));
            fclose($file);
            return true;
        } return false;
    }
    /**
     * Return a setting value from the config.
     * @param mixed $key the setting key, or an array as path to sub-key
     * @return mixed the setting value
     */
    public function config($key)
    {
        if(!is_array($key)) $key = array($key);
        $curr = $this->config;
        foreach($key as $k) {
            if(is_numeric($k)) {
                $k = intval($k);
                if(!isset($curr[$k])) return;
                $curr = $curr[$k];
            } else {
                if(!isset($curr->$k)) return;
                $curr = $curr->$k;
            }
            $k = $curr;
        }
        return $k;
    }


    /*
     *  PANEL
     */


    /**
     * Save settings if necessary and display configuration panel content
     * Parse and replace values in php config file by POST values.
     */
    public function panel()
    {
        if(isset($_POST['p01-contact']['settings'])) {
            $success = $this->update_config($_POST['p01-contact']['settings']);
            $this->load_config();

            if($success)  echo '<div class="updated">' . $this->lang('config_updated') . '</div>';
            else echo '<div class="error">'.$this->lang('config_error_modify').'<pre>'.CONFIGFILE.'</pre></div>';
        }
        echo $this->panel_content();
    }

    /**
     * Return configuration panel content, replacing the following in the template :
     *
     * - lang(key) : language string
     * - config(key,...) : value of a config setting
     * - other(key) : other value pre-defined
     * - VALUE : constant value
     *
     * @return string
     */
    private function panel_content()
    {
        $others = array();
        $others['disablechecked'] = $this->config('disable') ? 'checked="checked" ' : '';
        $others['debugchecked'] = $this->config('debug') ? 'checked="checked" ' : '';
        $others['default_lang'] = $this->default_lang;

        foreach($this->config('checklist') as $i => $cl) {
            $others['cl'.$i.'bl'] = isset($cl->type) && $cl->type == 'whitelist' ? '' : 'checked';
            $others['cl'.$i.'wl'] = $others['cl'.$i.'bl'] ? '' : 'checked';
        }

        $lang = $this->config('lang');
        $others['langsoptions'] = '<option value=""'.($lang==''?' selected="selected" ':'').'>Default</option>';
        foreach($this->langs() as $iso => $name) {
            $others['langsoptions'] .= '<option value="' . $iso . '" ';
            if($lang == $iso) $others['langsoptions'] .= 'selected="selected" ';
            $others['langsoptions'] .= '/>' . $name . '</option>';
        }

        $template = file_get_contents(ROOTDIR.'/settings_tpl.html');
        $template = preg_replace_callback('`(const|lang|config|other)\(([^)]+)\)`',
            function ($matches) use($others) {
                switch ($matches[1]) {
                    case 'lang': return $this->lang($matches[2]);
                    case 'config': return $this->config(explode(',', $matches[2]));
                    case 'other': if(isset($others[$matches[2]])) return $others[$matches[2]];
                    case 'const': return constant($matches[2]);
                }
            }, $template);

        //new release
        $versionblock = '';
        if($new = $this->get_new_release()) {
            $versionblock .= '<div class="updated">' . $this->lang('new_release');
            $versionblock .= '<br /><a href="' . $new->html_url . '">';
            $versionblock .= $this->lang('download') . ' (' . $new->name . ')</a></div>';
        }

        return $versionblock . $template;
    }


    /*
     *  TOKENS
     */


    /*
     * Create an unique hash in SESSION
     */
    private function set_token() {
        $_SESSION['p01-contact_token'] = uniqid(md5(microtime()), true);
    }
    /*
     * Get the token in SESSION (create it if not exists)
     * @return string
     */
    public function get_token() {
        if(!isset($_SESSION['p01-contact_token']))
            $this->set_token();
        return $_SESSION['p01-contact_token'];
    }
    /*
     * Compare the POSTed token to the SESSION one
     * @return boolean
     */
    private function check_token() {
        return $this->get_token() === $_POST['p01-contact_form']['token'];
    }
    /**
     * Return array of valid emails from a comma separated string
     * @param string $emails
     * @return array
     */
    public function get_valid_emails($emails) {
        return array_filter(explode(',', $emails), function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }
}

/*
 * Contact form class

 * Contains fields, manage mail sending.
 */
class P01contact_form
{
    public $P01contact;

    private $id;
    private $status;
    public $targets;
    private $fields;
    public $lang;

    /*
     * @param P01contact $P01contact
     * @param int $id the form id
     */
    public function __construct($P01contact, $id)
    {
        $this->P01contact = $P01contact;

        $this->id = $id;
        $this->status = '';
        $this->targets = array();
        $this->fields = array();
    }

    /*
     * Return the html display of the form
     * @return string the <form>
     */
    public function html()
    {
        $html  = '<form action="#p01-contact' . $this->id . '" autocomplete="off" ';
        $html .= 'id="p01-contact' . $this->id . '" class="p01-contact" method="post">';

        $html .= $this->html_status();

        if(($this->status != 'sent') && ($this->status != 'sent_copy')) {
            foreach($this->fields as $id => $field) $html .= $field->html();

            $html .= '<div><input name="p01-contact_form[id]" type="hidden" value="' . $this->id . '" />';
            $html .= '<input name="p01-contact_form[token]" type="hidden" value="' . $this->P01contact->get_token() . '" />';
            $html .= '<input class="submit" ';
            $html .= 'type="submit" value="' . $this->lang('send') . '" /></div>';
        }
        $html .= '</form>';

        return $html;
    }

    /*
     * Return an html display of the form status
     * @return string the <div>
     */
    private function html_status()
    {
        if(!$this->status) return '';
        if (($this->status == 'sent') || ($this->status == 'sent_copy')) {
            $statusclass = 'alert success';
        } else {
            $statusclass = 'alert failed';
        }
        return '<div class="' . $statusclass . '">' . $this->lang($this->status) . '</div>';
    }

    /*
     * Return an html http:// link
     * @param string $href the link address
     * @param string $title if not used, the link title will be the address
     * @return string the <a>
     */
    private function html_link($href, $title = False)
    {
        if(!$title) $title = $href;
        return '<a href="http://' . $href . '">' . $title . '</a>';
    }

    /*
     * Return an html mailto: link
     * @param string $href the email
     * @param string $title if not used, the link title will be the email
     * @return string the <a>
     */
    private function html_mail_link($href, $title = False)
    {
        if(!$title) $title = $href;
        return '<a href="mailto:' . $href . '">' . $title . '</a>';
    }

    /**
     * Send a mail based on form
     *
     * Create the mail content and headers along to settings, form
     * and fields datas; and update the form status (sent|error).
     */
    public function send_mail()
    {
        $server = $_SERVER['SERVER_NAME'];
        $uri = $_SERVER['REQUEST_URI'];

        // content
        $content = '';
        $skip_in_message = array('name','email','subject','captcha');
        foreach($this->fields as $field)
        {
            $title = !empty($field->title) ? $field->title : $field->type;

            if($field->type == 'name') $name = $field->value;
            if($field->type == 'email') $email = $field->value;
            if($field->type == 'subject') $subject = $field->value;

            if(!in_array($field->type, $skip_in_message) && !empty($field->value))
            {
                if($field->type != 'askcopy') // managed blow for him.
                    $content .= '<p><strong>' . $this->lang($field->title).' :</strong> ';
                switch($field->type)
                {
                    case 'message' :
                    case 'textarea' :
                        $content .= '<p style="margin:10px;padding:10px;border:1px solid silver">';
                        $content .= nl2br($field->value) . '</p>';
                        break;
                    case 'url' :
                        $content .= $this->html_link($field->value);
                        break;
                    case 'checkbox' :
                    case 'select' :
                    case 'radio' :
                        $content .= '<ul>';
                        foreach($field->value as $v)
                            if(isset($v[2]) && $v[2] == 'selected')
                                $content .=  '<li>' . $v[1] . '</li>';
                        $content .= '</ul>';
                        break;
                    case 'askcopy' :
                        $askcopy = in_array('selected', $field->value[0]);
                        $content .= '<p><strong>' . $this->lang('askedcopy').'.</strong></p>';
                        break;
                    default :
                        $content .=  $field->value;
                }
                $content .= '</p>';
            }
        }

        if(!isset($askcopy)) $askcopy = false;
        if(empty($email)) {
            $askcopy = false;
            $email = $this->lang('nofrom');
        }
        if(empty($name)) $name = $this->lang('nofrom');
        if(empty($subject)) $subject = $this->lang('nosubject');

        // title
        $title  = '<h2>' . $this->lang('fromsite') . ' <em>' . $server . '</em></h2>';
        $title .= '<h3>' . date('r') . '</h3>';
        $title .= '<p><strong>From :</strong> <a href="mailto:'.$email.'">'.$name.($email ? " &lt;$email&gt;":'') . '</a></p>';

        // footer infos
        $footer  = '<p><i>' . $this->lang('sentfrom');
        $footer .= ' ' . $this->html_link($server.$uri, $uri);
        $footer_copy = $footer . '</i></p>'; // version without infos below
        $footer .= '<br />If this mail should not be for you, please contact ';
        $footer .= $this->html_mail_link($this->targets[0]);
        $footer .= '</i></p>';

        $targets = implode(',', $this->targets);

        if(function_exists('mb_convert_encoding')) {
            $subject = mb_encode_mimeheader(html_entity_decode($subject, ENT_COMPAT, 'UTF-8'), 'UTF-8', 'Q');
            $name = mb_encode_mimeheader(html_entity_decode($name, ENT_COMPAT, 'UTF-8'), 'UTF-8', 'Q');
        }

        $headers  = "From: $encoded_name <$email>\r\n";
        $headers .= "Reply-To: $encoded_name <$email>\r\n";
        $headers .= "Return-Path: $encoded_name <$email>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n" ;

        if(!$this->config('debug')) {
            // send mail
            $status = mail($targets, $subject, $title.$content.$footer, $headers);
            if($status) {
                if($askcopy) { // send copy
                    $copy = mail($email, $subject, $title.$content.$footer_copy, $headers);
                    if($copy) $this->status = 'sent_copy';
                    else $this->status = 'error_copy';
                } else $this->status = 'sent';
            } else $this->status = 'error';
        } else {
            // display mail for debug
            echo '<h2 style="color:#c33">p01-contact (not) sent mail :</h2>';
            echo '<pre>'.htmlspecialchars($headers).'</pre>';
            echo "<pre>Targets: $targets\nHidden targets: $bcc\nSubject: $subject</pre>";
            echo '<div style="border:1px solid #ccc;padding:15px;">' . $title.$content.$footer . '</div>';
            $this->status = $this->lang('debug');
        }
    }

    /*
     * Reset all fields values and errors
     */
    public function reset()
    {
        foreach($this->fields as $id => $field) {
            $field->value = '';
            $field->error = '';
        }
    }

    /**
     * GETTERS / SETTERS
     */

    public function add_target($tget) {
        if(in_array($tget, $this->targets) === false)
            $this->targets[] = $tget;
    }
    public function set_targets(array $targets) {$this->targets = $targets;}
    public function count_targets() {return count($this->targets);}

    public function get_field($id) {return $this->fields[$id];}
    public function get_fields() {return $this->fields;}
    public function add_field(P01contact_field $field) {$this->fields[] = $field;}

    public function set_status($status) {
        if(is_string($status)) $this->status = $status;
    }

    public function get_id() {return $this->id;}
    public function get_status() {return $this->status;}

    public function config($key) {return $this->P01contact->config($key);}
    public function lang($key) {return $this->P01contact->lang($key, $this->lang);}
}

class P01contact_field
{
    private $form;

    public $id;
    public $type;
    public $title;
    public $description;
    public $value;
    public $required;
    public $locked;
    public $error;

    /**
     * @param P01contact_form $form the container form
     * @param int $id the field id
     * @param string $type the field type
     */
    public function __construct($form, $id, $type)
    {
        $this->form = $form;
        $this->id = $id;
        $this->type = $type;
    }

    /**
     * Check field value
     *
     * Check if field is empty and required or
     * not empty but not valid.
     * @return string the error key, or empty
     */
    public function check_content()
    {
        // empty and required
        if(empty($this->value) && $this->required) {
            return 'field_required';
        }
        // value blacklisted or not in whitelist
        if($reason = $this->check_blacklisted()) {
            return 'field_' . $reason;
        }
        // not empty but not valid
        if(!empty($this->value) && !$this->check_validity()) {
            return 'field_' . $this->type;
        }

        return '';
    }

    /**
     * Check if field value is valid
     * Mean different things depending on field type
     * @return boolean
     */
    public function check_validity()
    {
        switch($this->type) {
            case 'email':
                return filter_var($this->value, FILTER_VALIDATE_EMAIL);
            case 'tel':
                $pattern = '`^\+?[-0-9(). ]{6,}$$`i';
                return preg_match($pattern, $this->value);
            case 'url':
                return filter_var($this->value, FILTER_VALIDATE_URL);
            case 'message':
                return strlen($this->value) > $this->form->config('message_len');
            case 'captcha':
                return $this->reCaptcha_validity($this->value);
            case 'fieldcaptcha':
                return empty($this->value);
            case 'password':
                return $this->value == $this->required;
            default:
                return true;
        }
    }

    /**
     * Check if reCaptcha is valid
     * @return boolean
     */
    public function reCaptcha_validity($answer)
    {
        if (!$answer) return false;
        $params = [
            'secret'    => $this->form->config('recaptcha_secret_key'),
            'response'  => $answer
        ];
        $url = "https://www.google.com/recaptcha/api/siteverify?" . http_build_query($params);
        if (function_exists('curl_version')) {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
        } else {
            $response = file_get_contents($url);
        }

        if (empty($response) || is_null($response)) {
            return false;
        }

        return json_decode($response)->success;
    }

    /**
     * Check if field value is blacklisted
     *
     * Search for every comma-separated entry of every checklist
     * in value, and define if it should or should not be there.
     *
     * @return boolean
     */
    public function check_blacklisted()
    {
        $list = $this->form->config('checklist');
        foreach ($list as $i => $cl) {
            if($cl->name != $this->type) continue;
            $content = array_filter(explode(',', $cl->content));
            foreach($content as $avoid) {
                $found = preg_match("`$avoid`", $this->value);
                if($cl->type == 'blacklist' && $found) return $cl->type;
                if($cl->type == 'whitelist' && !$found) return $cl->type;
            }
        }
        return false;
    }

    /*
     * Return the html display of the field
     *
     * Manage field title, error message, and type-based display
     * @return string the <div>
     */
    public function html()
    {
        $id  = 'p01-contact' . $this->form->get_id() . '_field' . $this->id;
        $name = 'p01-contact_fields[' . $this->id . ']';
        $type = $this->general_type();
        $value = $this->value;
        $disabled = $this->locked ? ' disabled="disabled"' : '';
        $required = $this->required ? ' required ' : '';

        $html  = '<div class="field ' . $type.$required. '">';
        if($this->type != 'askcopy') // not needed here, the value say everything
            $html .= $this->html_label($id);

        switch($type)
        {
            case 'textarea' :
                $html .= '<textarea id="' . $id . '" rows="10" ';
                $html .= 'name="' . $name . '"' . $disabled.$required;
                $html .= '>' . $value . '</textarea>';
                break;
            case 'captcha' :
                $key = $this->form->config('recaptcha_public_key');
                $html .='<script src="https://www.google.com/recaptcha/api.js?onload=CaptchaCallback&render=explicit" async defer></script>
                <div class="recaptcha" id="'.$id.'"></div>';
                break;
            case 'fieldcaptcha' :
                $html .= '<input id="' . $id . '" type="text" name="' . $name . '" />';
                break;
            case 'checkbox' :
                foreach($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : '';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? ' checked' : '';
                    $html .= '<input id="' . $id . '_option' . $i . '"';
                    $html .= ' type="checkbox" name="' . $name . '[' . $i . ']"';
                    $html .= ' value="' . $value . '"' . $disabled.$required.$selected;
                    $html .= ' />' . $value;
                }
                break;
            case 'select' :
                $html .= '<select id="' . $id . '" name="' . $name . '"' . $disabled.$required . '>';
                foreach($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : ' Default';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? 'selected="selected"' : '';
                    $html .= '<option id="' . $id . '_option' . $i . '" value="' . $value;
                    $html .= '"' . $selected . ' >' . $value . '</option>';
                }
                $html.= '</select>';
                break;
            case 'radio' :
                foreach($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : ' Default';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? ' checked' : '';
                    $html .= '<input id="' . $id . '_option' . $i . '" type="radio" ';
                    $html .= 'name="' . $name . '" value="' . $value . '"';
                    $html .= $disabled.$required.$selected . ' />' . $value;
                }
                break;
            default :
                $html .= '<input id="' . $id . '" ';
                $html .= 'name="' . $name . '" type="'.$type.'" ';
                $html .= 'value="' . $value . '"' . $disabled.$required . ' />';
                break;
        }
        $html .= '</div>';
        return $html;
    }

    /*
     * Return the label of the field
     * @param string $for id of the target field
     * @return string the <div> (unclosed for captcha)
     */
    private function html_label($for)
    {
        $html = '<label for="' . $for . '">';
        if($this->title) {
            $html .= $this->title;
        } else $html .= ucfirst($this->form->lang($this->type));

        if($this->description) {
            $html .= ' <em class="description">' . $this->description . '</em>';
        }
        if($this->error) {
            $html .= ' <span class="error-msg">' . $this->form->lang($this->error) . '</span>';
        }
        $html .= '</label>';
        return $html;
    }

    /**
     * Return the general type of a field, even of specials fields.
     */
    function general_type()
    {
        $types = array(
            'name'    => 'text',
            'subject' => 'text',
            'address' => 'textarea',
            'message' => 'textarea',
            'askcopy' => 'checkbox'
        );
        if(isset($types[$this->type]))
            return $types[$this->type];
        else return $this->type;
    }
}

function preint($arr) {echo'<pre class="test" style="white-space:pre-wrap;">'; print_r(@$arr); echo '</pre>';}
function predump($arr) {echo'<pre class="test" style="white-space:pre-wrap;">';var_dump($arr);echo'</pre>';}
function unset_r($a,$i) {
    foreach($a as $k=>$v)
        if(isset($v[$i]))
            unset($a[$k][$i]);
    return $a;
}
?>
