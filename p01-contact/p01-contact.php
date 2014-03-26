<?php
/**
 * p01-contact - A simple contact forms manager
 *
 * @link http://nliautaud.fr/wiki/travaux/getsimple_p01-contact Documentation
 * @link http://get-simple.info/extend/plugin/p01-contact/35 Latest Version
 * @author Nicolas Liautaud <contact@nliautaud.fr>
 * @package p01-contact
 * @version 0.9.1
 */
if(session_id()=='') session_start();

class P01contact 
{
    public $version;
    public $default_email;
    public $default_lang;
    public $securimage_url;
    private $forms;

    public function __construct() 
    {
        $this->version = '0.9.1';
        $this->forms = array();
        $dir = dirname(__FILE__);
        define('LANGPATH', $dir . '/lang/');
        define('CONFIGPATH', $dir . '/config.php');
        define('CAPTCHAPATH', $dir . '/captcha/');
        
        define('DOCURL', 'http://nliautaud.fr/wiki/travaux/p01-contact');
        define('DOWNURL', 'http://get-simple.info/extend/plugin/p01-contact/35');
        define('FORUMURL', 'http://get-simple.info/forum/topic/1108');
        define('VERSIONURL', 'http://get-simple.info/api/extend/?id=35');
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
        $pattern = '`(?<!<code>)\(%\s*contact\s*(.*)\s*%\)`';  
        preg_match_all($pattern, $contents, $tags, PREG_SET_ORDER);
        $ids = array();
        
        // create forms structures from TAG
        foreach($tags as $tag) {
            $id = $this->new_form_id();
            $form = $this->parse_tag($id, $tag[1]);
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
        return $contents;
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
     * Parse a tag to create form structure
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
        
        $param_pattern = '`[,:]\s*([^ ,"=!]+)(!)?\s*("([^"]*)")?\s*((=&gt;|=)?\s*([^,]*))?\s*`';
        $targets_pattern = '`[,:]\s*([_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3}))`i';
        $values_pattern = '`(?:^|\|)\s*(?:"([^"]+)")?\s*([^| ]+)?`';
        
        // parse emails
        preg_match_all($targets_pattern, $tag, $targets);
        $targets = array_unique($targets[1]);
        // add targets
        if(empty($targets)) {
            $default_email = $this->get_default_email();
            if($default_email)
                $form->add_target($default_email);
        } else {
            $form->set_targets($targets);
        }
        // delete them from tag
        $rest = preg_replace($targets_pattern, '', $tag); 
        $rest = $this->format($rest);
        // parse parameters
        preg_match_all($param_pattern, $rest, $params, PREG_SET_ORDER);
        if(empty($params)) {
            $default = $this->settings('default_params');
            $default = $this->format($default);
            preg_match_all($param_pattern, ': ' . $default, $params, PREG_SET_ORDER);
        }
        // add fields
        foreach($params as $id => $param) {
            $field = new P01contact_field($form, $id, $param[1]);
            $field->set_title($param[4]);
            
            if($param[1] == 'select'
            || $param[1] == 'radio'
            || $param[1] == 'checkbox') {
                // fields with multiples values
                preg_match_all($values_pattern, $param[7], $values, PREG_SET_ORDER);
                $values = unset_r($values, 0); 
                $field->set_value($values);
            }
            elseif($param[1] == 'askcopy') {
                // create checkbox-like structure
                $field->set_value(array(array(1 => $this->lang('askcopy'))));
            }
            elseif($param[1] == 'password') {
                // password value is required value
                $field->set_required($param[7]);
            }
            else $field->set_value($param[7]);
            
            
            if($param[1] != 'password')
                $field->set_required($param[2] == '!' ? True : False);
            $field->set_locked($param[6] == '=&gt;' ? True : False);
            $form->add_field($field);
        }
        
        return $form;
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
            foreach($this->format_data($_POST['p01-contact_fields']) as $field_id => $field_post)
            {
                $field = $form->get_field($field_id);
                
                // for multiple-values fields, posted value define selection
                $value = $field->get_value();
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
                    $field->set_value($value);
                }
                // for unique value fields, posted value define value
                else $field->set_value($field_post);
                
                $check = $field->check_content();
                $field->set_error($check);
                if($check) $errors = True;
            }
            // SECURITY : check tokens
            if(!$this->check_token()) {
                $form->set_status('token');
                $this->set_token();
                $form->reset();
            }
            // try to send mail
            elseif(!isset($errors)) {
                if($this->settings('enable') === False) {
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
        ini_set('display_errors', 'on');
        error_reporting(E_ALL);
        echo'<h2 style="color:#c33">p01-contact debug</h2>';
        if(!empty($_POST)) {
            echo'<h3>$_POST :</h3>';
            echo'<pre>';
            @print_r($_POST);
            echo'</pre>';
        }
        echo'<h3>$p01contact :</h3>';
        echo'<pre>';
        print_r($this);
        echo'</pre>';
    }
    
    /**
     * Return a setting value from config file
     * @param string $key the setting key
     * @return mixed the setting value
     */
    public function settings($key)
    {
        require CONFIGPATH;
        if(isset($p01contact_settings[$key]))
            return $p01contact_settings[$key];
    }
    
    /**
     * Format array values
     *
     * For aesthetic and security, and recursive.
     * @param array $array
     * @return array
     */
    private function format_data($array)
    {
        foreach($array as $key => $val) {
            if(is_array($val)) $this->format_data($array[$key]);
            else {
                $tmp = stripslashes($val);
                $tmp = htmlentities($tmp, ENT_QUOTES, 'UTF-8');
                $array[$key] = $tmp;
            }
        }
        return $array;
    }
    
    /**
     * Return a traduction of the keyword
     *
     * Manage languages between requested langs and existing traductions.
     * @param string $key the keyword
     * @return string
     */
    public function lang($key)
    {
        global $p01contact_lang;
        
        $lang = $this->settings('lang');
        $lang = empty($lang) ? $this->default_lang : $lang;
        
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
    
    /**
     * Return the last version of p01-contact in GS
     * @return string
     */
    private function last_version()
    {
        $apiback = file_get_contents(VERSIONURL);
        $response = json_decode($apiback);
        if ($response->status == 'successful') {
            return $response->version;
        }
    }
    /**
     * Check if a new version exists. Return version number if exists, or False.
     * @return mixed
     */
    private function exists_new_version()
    {
        $actual = explode('.', $this->version);
        $last = $this->last_version();
        $last_r = explode('.', $last);
        foreach($actual as $key => $val)
            if(isset($last_r[$key])
            && $val < $last_r[$key])
                return $last;
        return False;
    }

    /**
     * Save settings if necessary and display configuration panel content
     * Parse and replace values in php config file by POST values.
     */
    public function panel()
    {
        if(isset($_POST['p01-contact']['settings'])) {
            $data = $this->format_data($_POST['p01-contact']['settings']);
            if($content = file_get_contents(CONFIGPATH)) {
            
                $enable  = isset($data['enable']) ? 'True' : 'False';
                $content = preg_replace("`('enable' => )(True|False)`", "\\1$enable", $content);
                $debug   = isset($data['debug']) ? 'True' : 'False';
                $content = preg_replace("`('debug' => )(True|False)`", "\\1$debug", $content);
                $content = preg_replace("`('lang' => ')[a-z]*'`", "\\1{$data['lang']}'", $content);
                $content = preg_replace("`('default_params' => ')[^']*'`", "\\1{$data['default_params']}'", $content);
                $content = preg_replace("`('default_email' => ')[^']*'`", "\\1{$data['default_email']}'", $content);
                $content = preg_replace("`'message_len' => [0-9]+`", "'message_len' => {$data['message_len']}", $content);
                
                foreach($data['checklist'] as $key => $val) {
                    $content = preg_replace("`('checklist_$key' => ')[^']*'`", "\\1$val'", $content);
                }
                if(file_exists(CONFIGPATH)
                && $file = fopen(CONFIGPATH, 'w')) {
                    fwrite($file, $content);
                    fclose($file);
                    
                    global $p01contact_settings;
                    require(CONFIGPATH);
                    $updated = '<div class="updated">' . $this->lang('config_updated') . '</div>';
                } else {
                    $error = $this->lang('config_error_modify');
                }
            } else {
                $error = $this->lang('config_error_open');
            }
        }
        if(isset($updated)) echo $updated;
        elseif(isset($errors)) {
            echo '<div class="error">' . $error . '<pre>' . CONFIGPATH . '</pre></div>';
        }
        echo $this->panel_content();
    }
    
    /**
     * Return configuration panel content
     *
     * Display informations, parse config file and display settings form.
     * @return string
     */
    private function panel_content()
    {
        global $p01contact_settings;
        $config_file = file_get_contents(CONFIGPATH, true); //true: use_include_path
        $pattern = '`/\*[^*]*\* ([^*]*).*\* ([^*]*)[^*]*(\* ([^*]*))?\*/`';
        preg_match_all($pattern, $config_file, $descs);
        
        $c = '<h2>' . $this->lang('config_title') . '</h2>';
        
        //new release
        if($newversion = $this->exists_new_version()) {
            $c.= '<div class="updated">' . $this->lang('new_release');
            $c.= '<br /><a href="' . DOWNURL . '">';
            $c.= $this->lang('download') . ' (' . $newversion . ')</a></div>';
        }
        //links
        $c.= '<p><a href="' . DOCURL . '">' . $this->lang('doc') . '</a>';
        $c.= ' - <a href="' . FORUMURL . '">' . $this->lang('forum') . '</a></p>';
        
        $c.= '<form action="" method="post"><table>';
        
        //enable
        $c.= '<tr><td><b><label style="display:block;float:none">' . $this->lang('enable') . '</label></b>';
        $c.= '<i>' . $this->lang('enable_sub') . '</i></td>';
        $c.= '<td><input type="checkbox" name="p01-contact[settings][enable]" ';
        $c.= $this->settings('enable') ? 'checked="checked" ' : '';
        $c.= '/></td></tr>';
        
        //default email
        $c.= '<tr><td><b><label style="display:block;float:none">';
        $c.= $this->lang('default_email') . '</label></b>';
        $c.= '<i>' . $this->lang('default_email_sub') . ' ';
        $c.= ($this->default_email ? $this->default_email : '"not set"') . '</i></td><td>';
        $c.= '<input type="text" name="p01-contact[settings][default_email]" ';
        $settings_email = $this->settings('default_email');
        $c.= 'value="' . $settings_email . '" />';
        $c.= '</td></tr>';
        
        // language
        $c.= '<tr><td><b><label style="display:block;float:none">' . $this->lang('lang') . '</label></b>';
        $c.= '<i>' . $this->lang('lang_sub') . ' ' . $this->default_lang . '</i></td>';
        $c.= '</td><td><select name="p01-contact[settings][lang]">';
        $lang = $this->settings('lang');
        $c.= '<option value=""' . ($lang == ''?' selected="selected" ':'') . '>Default</option>';
        foreach($this->langs() as $iso => $name) {
            $c.= '<option value="' . $iso . '" ';
            if($lang == $iso) $c.= 'selected="selected" ';
            $c.= '/>' . $name . '</option>';
        }
        $c.= '</select></td></tr>';
        
        //message length
        $c.= '<tr><td><b><label style="display:block;float:none">' . $this->lang('message_len') . '</label></b>';
        $c.= '<i>' . $this->lang('message_len_sub') . '</i></td>';
        $c.= '<td><input type="text" name="p01-contact[settings][message_len]" size=3 maxlength=3 ';
        $c.= 'value="' . $this->settings('message_len').'" /></td></tr>';
        
        // default parameters
        $c.= '<tr><td colspan="2"><b><label style="display:block;float:none">';
        $c.= $this->lang('default_params') . '</label></b>';
        $c.= '<i>' . $this->lang('default_params_sub') . '</i><br />';
        $c.= '<textarea name="p01-contact[settings][default_params]" style="width:100%;height:40px">';
        $c.= $this->settings('default_params');
        $c.= '</textarea></td></tr>';
        
        //checklists
        $c.= '<tr><td colspan="2"><b><label style="display:block;float:none">';
        $c.= $this->lang('checklists') . '</label></b>';
        $c.= '<i>' . $this->lang('checklists_sub') . '</i>';
        $fields = array(
            'general_fields' => array('text','textarea'), 'special_fields' =>
            array('name','email','address','phone','website','subject','message'));
        foreach($fields as $type => $f)
            foreach($f as $id=>$field) {
                if(!$id) $c.= '<p></p><p><b>' . $this->lang($type) . ' :</b></p>';
                $content = $this->settings('checklist_'.$field);
                $c.= '<div><b>' . ucfirst($field);
                $c.= ' </b><input name="p01-contact[settings][checklist_type]['.$field.']"';
                $c.= ' type="radio" value="blacklist" checked /> ' . $this->lang('blacklist');
                $c.= ' <input name="p01-contact[settings][checklist_type]['.$field.']"';
                $c.= ' type="radio" value="whitelist" disabled /> ' . $this->lang('whitelist') . '</div>';
                $c.= '<textarea name="p01-contact[settings][checklist]['.$field.']" ';
                $c.= 'style="width:100%;height:'.(40+strlen($content)*0.2).'px">';
                $c.= $content . '</textarea>';
        }
        $c.= '</tr></td>';
        
        //debug
        $c.= '<tr><td><b><label style="display:block;float:none">' . $this->lang('debug') . '</label></b>';
        $c.= '<i>' . $this->lang('debug_sub') . '</i><br />';
        $c.= '<b>' . $this->lang('debug_warn') . '</b></td>';
        $c.= '<td><input type="checkbox" name="p01-contact[settings][debug]" ';
        $c.= $this->settings('debug') ? 'checked="checked" ' : '';
        $c.= '/></td></tr>';
        
        $c.= '<tr><td><input type="submit" value="Save settings" /></td></tr>';
        
        $c.= '</table></form>';
        
        return $c;
    }
    
    /*
     * Create an unique hash in SESSION
     */
    private function set_token()
    {
        $_SESSION['p01-contact_token'] = uniqid(md5(microtime()), True);
    }
    /*
     * Get the token in SESSION (create it if not exists)
     * @return string
     */
    public function get_token()
    {
        if(!isset($_SESSION['p01-contact_token']))
            $this->set_token();
        return $_SESSION['p01-contact_token'];
    }
    /*
     * Compare the POSTed token to the SESSION one
     * @return boolean
     */
    private function check_token()
    {
        if($this->get_token() === $_POST['p01-contact_form']['token'])
            return True;
        else return False;
    }
    
    /*
     * Return settings default email if set and valid,
     * or $this->default_email if set and valid,
     * or False.
     */
    public function get_default_email()
    {
        $settings_email = $this->settings('default_email');
        $pattern = '`^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$`i';
        
        if(!empty($settings_email)
        && preg_match($pattern, $settings_email))
            return $settings_email;
        if(!empty($this->default_email)
        && preg_match($pattern, $this->default_email))
            return $this->default_email;
        
        return False;
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
    private $targets;
    private $fields;

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
        
        if($this->status != 'sent') {
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
        $style = '
	        margin:0 0 20px 0;
	        background:#FCFBB8; 
	        line-height:30px;
	        padding:0 10px;
	        border:1px solid #F9CF51;
	        border-radius: 5px;
	        -moz-border-radius: 5px;
	        -khtml-border-radius: 5px;
	        -webkit-border-radius: 5px;';
        $style .= $this->status == 'sent' ? 'color:#308000;' : 'color:#D94136;';
        
        return '<div style="'.$style.'">' . $this->lang($this->status) . '</div>';
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
        
        // title
        $content  = '<h2>' . $this->lang('fromsite') . ' <i>' . $_SERVER['SERVER_NAME'] . '</i></h2>';
        $content .= '<h3>' . date('r') . '</h3><br/>';
        
        // fields
        $skip = array('captcha');
        foreach($this->fields as $field)
        {
            $type  = $field->get_type();
            $value = $field->get_value();
            $title = $field->get_title();
            if($type == 'name')
                $name = $value;
            elseif($type == 'email')
                $email = $value;
            elseif($type == 'subject')
                $subject = $value;
            elseif(
                !in_array($type, $skip)
                && !empty($value))
            {
                $title = !empty($title) ? $title : $type;
                if($type != 'askcopy') // managed blow for him.
                    $content .= '<p><b>' . $this->lang($title).' :</b> ';
                switch($type)
                {
                    case 'message' :
                    case 'textarea' :
                        $content .= '<p style="margin:10px;padding:10px;border:1px solid silver">';
                        $content .= nl2br($value) . '</p>';
                        break;
                    case 'website' :
                        $content .= $this->html_link($value);
                        break;
                    case 'checkbox' :
                    case 'select' :
                    case 'radio' :
                        $content .= '<ul>';
                        foreach($value as $v)
                            if(isset($v[2]) && $v[2] == 'selected')
                                $content .=  '<li>' . $v[1] . '</li>';
                        $content .= '</ul>';
                        break;
                    case 'askcopy' :
                        $askcopy = True;
                        $content .= '<p><b>' . $this->lang('askedcopy').'.</b></p>';
                        break;
                    default :
                        $content .=  $value;
                } 
                $content .= '</p>';
            }
        }
        if(!isset($askcopy)) $askcopy = False;
        
        // footer infos
        $footer  = '<p><i>' . $this->lang('sentfrom');
        $footer .= ' ' . $this->html_link($server.$uri, $uri);
        $footer_copy = $footer . '</i></p>'; // version without infos below
        $footer .= '<br />If this mail should not be for you, please contact ';
        $footer .= $this->html_mail_link($this->P01contact->get_default_email());
        $footer .= '</i></p>';
        
        $targets = implode(',', $this->targets);
        
        if(empty($name)) $name = $this->lang('nofrom');
        if(empty($email)) {
            $askcopy = False;
            $email = $this->lang('nofrom');
        }
        if(empty($subject)) $subject = $this->lang('nosubject');
        $subject = '=?utf-8?B?' . base64_encode($subject) . '?=';
        
        $headers  = "From: $name <$email>\r\n";
        $headers .= "Reply-To: $name <$email>\r\n";
        $headers .= "Return-Path: $name <$email>\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n" ;
        
        if(!$this->settings('debug')) {
            // send mail
            $status = mail($targets, $subject, $content.$footer, $headers);
            if($status) {
                if($askcopy) { // send copy
                    $copy = mail($email, $subject, $content.$footer_copy, $headers);
                    if($copy) $this->status = 'sent_copy';
                    else $this->status = 'error_copy';
                } else $this->status = 'sent';
            } else $this->status = 'error';
        } else {
            // display mail for debug
            echo'<h2 style="color:#c33">p01-contact (not) sent mail :</h2>';
            echo'<pre>' . $headers . '</pre>';
            echo'<div style="border:1px solid black;padding:10px">' . $content.$footer . '</div>';
            $this->status = $this->lang('debug');
        }            
    }
    
    /*
     * Reset all fields values and errors
     */
    public function reset()
    {
        foreach($this->fields as $id => $field) {
            $field->set_value('');
            $field->set_error('');
        }
    }

    /**
     * GETTERS / SETTERS
     */
    
    public function add_target($tget) {$this->targets[] = $tget;}
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
    
    public function settings($key) {return $this->P01contact->settings($key);}
    public function lang($key) {return $this->P01contact->lang($key);}
} 

class P01contact_field 
{
    private $form;
    
    private $id;
    private $type;
    private $title;
    private $value;
    private $required;
    private $locked;
    private $error;

    /*
     * @param P01contact_form $form the container form
     * @param int $id the field id
     * @param string $type the field type
     */
    public function __construct($form, $id, $type) 
    { 
        $this->form = $form;
        
        $this->id = $id;
        $this->type = $type;
        $this->title = '';
        $this->value = '';
        $this->required = False;
        $this->locked = False;
        $this->error = '';
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
        if(empty($this->value) && $this->required) {
            // empty and required
            return 'field_required';
        }
        elseif(!empty($this->value) && !$this->check_validity()) {
            // not empty but not valid
            return 'field_' . $this->type;
        }
        else return '';
    }

    /**
     * Check if field value is valid
     * Mean different things depending on field type
     * @return boolean
     */
    public function check_validity()
    {
        if($this->blacklisted()) return False;
        
        switch($this->type) {
            case 'email':
                $pattern = '`^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$`i';
                if(preg_match($pattern, $this->value)) return True;
                else return False;
            case 'phone':
                $pattern = '`^\+?[-0-9(). ]{6,}$$`i';
                if(preg_match($pattern, $this->value)) return True;
                else return False;
            case 'website':
                $pattern = "`^((http|https|ftp):\/\/(www\.)?|www\.)[a-zA-Z0-9\_\-]+\.([a-zA-Z]{2,4}|[a-zA-Z]{2}\.[a-zA-Z]{2})(\/[a-zA-Z0-9\-\._\?\&=,'\+%\$#~]*)*$`i";
                if(preg_match($pattern, $this->value)) return 1;
                else return False;
            case 'message':
                $size = strlen($this->value);
                if($size > $this->form->settings('message_len')) return True;
                else return False;
            case 'captcha':
                include_once CAPTCHAPATH . 'securimage.php';
                $securimage = new Securimage();
                if($securimage->check($this->value) == False)
                    return False;
                else return True;
            case 'fieldcaptcha':
                if(!empty($this->value)) return False;
                else return True; 
            case 'password':
                if($this->value == $this->required)
                    return True;
                else return False; 
            default:
                return True;
        }
    }

    /**
     * Check if field value is blacklisted
     *
     * Search any entry of config file field type
     * blacklist in field value.
     * @return boolean
     */
    public function blacklisted()
    {
        $list = $this->form->settings('checklist_' . $this->type);
        if(empty($list)) return False;

        $array = explode(',', $list);
        foreach($array as $avoid) {
            if(preg_match('`' . $avoid . '`', $this->value))
                return True;
        }
        return False;
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
        
        $html  = '
        <div class="field ' . $type . '">';
        if($this->type != 'askcopy') // not needed here, the value say everything
            $html .= $this->html_label($id);
        
        switch($type) {
            case 'text' :
                $html .= '
                <input id="' . $id . '" ';
                $html .= 'name="' . $name . '" type="text" ';
                $html .= 'value="' . $value . '"' . $disabled . ' />';
                break;
            case 'textarea' :
                $html .= '
                <textarea id="' . $id . '" rows="10" ';
                $html .= 'name="' . $name . '"' . $disabled;
                $html .= '>' . $value . '</textarea>';
                break;
            case 'captcha' :
                $html .= '
                    <div class="captchaimg">';
                $html .= '<img id="captchaimg" ';
                $html .=    'src="' . $this->securimage_url() . 'securimage_show.php" ';
                $html .=    'alt="CAPTCHA Image" />';
                $html .= '</div></label></div>
                <a href="#"';
                $html .= 'onclick="document.getElementById(\'captchaimg\').src = ';
                $html .= '\'' . $this->securimage_url() . 'securimage_show.php?\' ';
                $html .= '+ Math.random(); return false">';
                $html .= $this->form->lang('reload');
                $html .= '</a>
                <input id="' . $id . '" ';
                $html .= 'type="text" name="' . $name . '" ';
                $html .= 'size="10" maxlength="6"' . $disabled . ' />';
                break;
            case 'fieldcaptcha' :
                $html .= '<input id="' . $id . '" type="text" name="' . $name . '" />';
                break;
            case 'checkbox' :
                foreach($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : '';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? ' checked' : '';
                    $html .= '
                    <input id="' . $id . '_option' . $i . '"';
                    $html .= ' type="checkbox" name="' . $name . '[' . $i . ']"';
                    $html .= ' value="' . $value . '"' . $disabled . $selected;
                    $html .= ' />' . $value;
                }
                break;
            case 'select' :
                $html .= '
                <select id="' . $id . '" name="' . $name . '"' . $disabled . '>';
                foreach($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : ' Default';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? 'selected="selected"' : '';
                    $html .= '
                    <option id="' . $id . '_option' . $i . '" value="' . $value;
                    $html .= '"' . $selected . ' >' . $value . '</option>';
                }
                $html.= '</select>';
                break;
            case 'radio' :
                foreach($this->value as $i => $v) {
                    $value = !empty($v[1]) ? ' ' . $v[1] : ' Default';
                    $selected = !empty($v[2]) && $v[2] == 'selected' ? ' checked' : '';
                    $html .= ' 
                    <input id="' . $id . '_option' . $i . '" type="radio" ';
                    $html .= 'name="' . $name . '" value="' . $value . '"';
                    $html .= $disabled . $selected . ' />' . $value;
                }
                break;
            case 'password' :
                $html .= '
                <input id="' . $id . '" ';
                $html .= 'name="' . $name . '" type="password" ';
                $html .= $disabled . ' />';
                break;
            //case 'file' :
                $html .= '<input id="' . $id . '" ';
                $html .= 'type="file" name="' . $name . '"' . $disabled . ' />';
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
        $html  = '
                <div class="label">';
        $html .= '<label for="' . $for . '">';
        if(!empty($this->title)) {
            $html .= $this->title;
        } else $html .= ucfirst($this->form->lang($this->type));
        
        $html .= $this->required ? ' <strong style="color:red">*</strong>' : '';
        
        if(!empty($this->error)) {
            $html .= ' <span style="font-size:0.7em;color:red">';
            $html .= $this->form->lang($this->error);
            $html .= '</span>';
        }
        if($this->type != 'captcha') { // captcha close label after image
            $html .= '</label></div>';
        }
        return $html;
    }
        
    /**
     * Return the general type of a field, even of specials fields.
     */
    function general_type()
    {
        $types = array(
            'name'    => 'text',
            'email'   => 'text',
            'phone'   => 'text',
            'website' => 'text',
            'subject' => 'text',
            'address' => 'textarea',
            'message' => 'textarea',
            'file'    => 'file',
            'captcha' => 'captcha',
            'askcopy' => 'checkbox');
        if(isset($types[$this->type]))
            return $types[$this->type];
        else return $this->type;
    }
    
    /**
     * GETTERS / SETTERS
     */
     
    public function get_type() {return $this->type;}
    
    public function get_title() {return $this->title;}
    public function set_title($title) {
        if(is_string($title)) $this->title = $title;
    }
    
    public function get_value() {return $this->value;}
    public function set_value($value) {
        if(is_string($value)
        || is_array($value))
        $this->value = $value;
    }
    
    public function set_required($required) {
        $this->required = $required;
    }
    public function set_locked($locked) {
        if(is_bool($locked)) $this->locked = $locked;
    }
    public function set_error($error) {
        if(is_string($error)) $this->error = $error;
    }
    public function securimage_url() {
        return $this->form->P01contact->securimage_url;
    }
}

function preint($arr) {echo'<pre>';var_dump($arr);echo'</pre>';}
function unset_r($a,$i) {
    foreach($a as $k=>$v)
        if(isset($v[$i]))
            unset($a[$k][$i]);
    return $a;
}
?>
