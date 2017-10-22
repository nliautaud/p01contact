<?php
/**
 * p01-contact - A simple contact forms manager
 *
 * @package p01-contact
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 */
namespace P01C;

require_once 'P01contact_Form.php';
require_once 'vendor/spyc.php';

if (session_id() === '') {
    session_start();
}

class P01contact
{
    public $version;
    public $default_lang;
    private $config;
    private $langs;

    public function __construct()
    {
        define('P01C\VERSION', '1.0.1');
        $this->version = VERSION;

        define('P01C\SERVERNAME', $_SERVER['SERVER_NAME']);
        define('P01C\SERVER', 'http://' . SERVERNAME);
        define('P01C\PAGEURI', $_SERVER['REQUEST_URI']);
        define('P01C\PAGEURL', SERVER . PAGEURI);

        define('P01C\PATH', dirname(dirname(__FILE__)) . '/');
        define('P01C\RELPATH', substr(PATH, strlen($_SERVER['DOCUMENT_ROOT'])));

        define('P01C\LANGSPATH', PATH . 'lang/');
        define('P01C\TPLPATH', PATH . 'src/templates/');
        define('P01C\CONFIGPATH', PATH . 'config.json');
        define('P01C\LOGPATH', PATH . 'log.json');

        define('P01C\REPOURL', 'https://github.com/nliautaud/p01contact');
        define('P01C\WIKIURL', 'https://github.com/nliautaud/p01contact/wiki');
        define('P01C\ISSUESURL', 'https://github.com/nliautaud/p01contact/issues');
        define('P01C\APILATEST', 'https://api.github.com/repos/nliautaud/p01contact/releases/latest');

        $this->loadConfig();
        $this->loadLangs();
    }

    /**
     * Query the releases API and return the new release infos, if there is one.
     *
     * @see https://developer.github.com/v3/repos/releases/#get-the-latest-release
     * @return object the release infos
     */
    public function getNewRelease()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,APILATEST);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'p01contact/curl');
        $resp = curl_exec($ch);
        curl_close($ch);
        if ($resp) return json_decode($resp);
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
        $sp = '(?:\s|</?p>)*';
        $pattern = "`(?<!<code>)\(%\s*contact\s*(\w*)\s*:?$sp(.*?)$sp%\)`s";
        preg_match_all($pattern, $contents, $tags, PREG_SET_ORDER);

        static $once;
        if (!$once) {
            $inc = '<link rel="stylesheet" href="'.SERVER.RELPATH.'style.css"/>';
            $contents = $inc . $contents;
            $once = true;
        }

        foreach ($tags as $tag) {
            $form = new P01contactForm($this);
            $form->parseTag($tag[2]);
            $form->lang = $tag[1];
            $form->post();
            $contents = preg_replace($pattern, $form->html(), $contents, 1);
        }
        $_SESSION['p01-contact']['last_page_load'] = time();

        return $contents;
    }

    /**
     * Enable PHP error reporting and display system and p01-contact infos.
     */
    public function debug()
    {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $health = 'PHP version : '.phpversion()."\n";
        $health.= 'PHP mbstring (UTF-8) : '.(extension_loaded('mbstring') ? 'OK' : 'MISSING');

        echo'<h2 style="color:#c33">p01-contact debug</h2>';

        echo'<h3>Health :</h3>';
        preint($health);

        echo'<h3>Constants :</h3>';
        preint(array_filter(get_defined_constants(true)['user'], function ($n) {
            return 0 === strpos($n, __namespace__);
        }, ARRAY_FILTER_USE_KEY));

        if (!empty($_SESSION)) {
            echo'<h3>$_SESSION :</h3>';
            preint($_SESSION);
        }
        if (!empty($_POST)) {
            echo'<h3>$_POST :</h3>';
            preint($_POST);
        }
        echo'<h3>$p01contact :</h3>';
        preint($this);
    }


    /*
     *  LANG
     */


    /**
     * Load language files
     */
    private function loadLangs()
    {
        $this->langs = [];
        $files = glob(LANGSPATH . '*.yml');
        foreach ($files as $f) {
            $parsed = \Spyc::YAMLLoad($f);
            if(!$parsed || !isset($parsed['key'])) continue;
            $this->langs[$parsed['key']] = $parsed;
        }
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
        if (!$lang) {
            $lang = $this->config('lang');
            $lang = empty($lang) ? $this->default_lang : $lang;
        }
        if (empty($lang) || !isset($this->langs[$lang])) {
            $lang = 'en';
        }
        if (isset($this->langs[$lang]['strings'][$key])) {
            return $this->langs[$lang]['strings'][$key];
        }
        return ucfirst($key);
    }
    /**
     * Return the languages objects
     * @return array
     */
    public function langs()
    {
        return $this->langs;
    }


    /*
     *  CONFIG
     */


    /**
     * Load the JSON configuration file.
     */
    private function loadConfig()
    {
        $content = file_exists(CONFIGPATH) ? file_get_contents(CONFIGPATH) : null;
        $this->config = $content ? json_decode($content) : (object) array();
        $this->setDefaultConfig();
    }

    /**
     * Set the obligatory settings if missing.
     */
    private function setDefaultConfig()
    {
        $default = array(
            'default_params' => 'name!, email!, subject!, message!',
            'separator' => ',',
            'logs_count' => 10,
            'use_honeypot' => true,
            'min_sec_after_load' => '3',
            'max_posts_by_hour' => '10',
            'min_sec_between_posts' => '5',
        );
        foreach ($default as $key => $value) {
            if (empty($this->config->{$key})) {
                $this->config->{$key} = $value;
            }
        }
    }

    /**
     * Add an entry to the logs.
     */
    public function log($data)
    {
        if (!$this->config('logs_count')) {
            return;
        }
        $logs = json_decode(@file_get_contents(LOGPATH));
        $logs[] = $data;
        $max = max(0, intval($this->config('logs_count')));

        while (count($logs) > $max) {
            array_shift($logs);
        }
        $this->updateJSON(LOGPATH, $logs);
    }

    /**
     * Update a JSON file with new data.
     *
     * @param string $file_path the config file path
     * @param array $new_values the new values to write
     * @param array $old_values the values to change
     * @return boolean file edition sucess
     */
    private function updateJSON($path, $new_values)
    {
        if ($file = fopen($path, 'w')) {
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
        if (!is_array($key)) {
            $key = array($key);
        }
        $curr = $this->config;
        foreach ($key as $k) {
            if (is_numeric($k)) {
                $k = intval($k);
                if (!isset($curr[$k])) {
                    return;
                }
                $curr = $curr[$k];
            } else {
                if (!isset($curr->$k)) {
                    return;
                }
                $curr = $curr->$k;
            }
            $k = $curr;
        }
        return $k;
    }


    /*
     *  TEMPLATES
     */


    /**
     * Return a template file content
     */
    public function getTemplate($name)
    {
        static $cache;
        if (isset($cache[$name])) {
            return $cache[$name];
        }
        if (!isset($cache)) {
            $cache = array();
        }
        $cache[$name] = @file_get_contents(TPLPATH . $name . '.html');
        return $cache[$name];
    }

    /**
     * Set the obligatory settings if missing.
     */
    public function renderTemplate($name, $data)
    {
        $html = $this->getTemplate($name);
        // config
        $html = preg_replace_callback('`config\((.+)\)`', function ($matches) {
            return $this->config(explode(',', $matches[1]));
        }, $html);
        // lang
        $html = preg_replace_callback('`{{lang\.(\w+)}}`', function ($matches) {
            return $this->lang($matches[1]);
        }, $html);
        // constants
        $html = preg_replace_callback('`{{([A-Z]{3,})}}`', function ($matches) {
            return constant(__namespace__.'\\'.$matches[1]);
        }, $html);
        // data
        $html = preg_replace_callback('`{{(\w+)}}`', function ($matches) use ($data) {
            return @$data->{$matches[1]};
        }, $html);
        return $html;
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
        if (isset($_POST['p01-contact']['settings'])) {
            $success = $this->updateJSON(CONFIGPATH, $_POST['p01-contact']['settings']);
            $this->loadConfig();

            if ($success) {
                echo '<div class="updated">' . $this->lang('config_updated') . '</div>';
            } else {
                echo '<div class="error">'.$this->lang('config_error_modify');
                echo '<pre>'.CONFIGPATH.'</pre></div>';
            }
        }
        echo $this->panelContent();
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
    private function panelContent($system = 'gs')
    {
        $debug = $this->config('debug');
        $tpl_data = (object) null;
        $tpl_data->disablechecked = $this->config('disable') ? 'checked="checked" ' : '';
        $tpl_data->debugchecked = $debug ? 'checked="checked" ' : '';
        $tpl_data->honeypotchecked = $this->config('use_honeypot') ? 'checked="checked" ' : '';
        $tpl_data->default_lang = $this->default_lang;
        $tpl_data->version = $this->version;

        $list = $this->config('checklist');
        if ($list) {
            foreach ($list as $i => $cl) {
                $bl = 'cl'.$i.'bl';
                $wl = 'cl'.$i.'wl';
                $tpl_data->$bl = isset($cl->type) && $cl->type == 'whitelist' ? '' : 'checked';
                $tpl_data->$wl = $tpl_data->$bl ? '' : 'checked';
            }
        }

        $lang = $this->config('lang');
        $tpl_data->langsoptions = '<option value=""'.($lang==''?' selected="selected" ':'').'>Default</option>';
        foreach ($this->langs() as $language) {
            $tpl_data->langsoptions .= '<option value="' . $language['lang_key'] . '" ';
            if ($lang == $language['lang_key']) {
                $tpl_data->langsoptions .= 'selected="selected" ';
            }
            $tpl_data->langsoptions .= '/>' . $language['lang_name'] . '</option>';
        }

        $html = $this->renderTemplate($system.'_settings', $tpl_data);

        //new release
        $infos = '';
        if ($response = $this->getNewRelease()) {
            if ($debug && isset($response->message)) {
                $infos .= '<div class="updated">New release check error debug : Github ';
                $infos .= $response->message . '</div>';
            }
            if (isset($response->url) && version_compare($response->tag_name, $this->version) > 0) {
                $infos .= '<div class="updated">' . $this->lang('new_release');
                $infos .= '<br /><a href="' . $response->html_url . '">';
                $infos .= $this->lang('download') . ' (' . $response->tag_name . ')</a></div>';
            }
        }

        $logsblock = $this->logsTable();

        return $infos . $html . $logsblock;
    }

    private function logsTable()
    {
        $logs = json_decode(@file_get_contents(LOGPATH));
        if (!$logs) {
            return;
        }
        $html = '';
        foreach (array_reverse ($logs) as $log) {
            $html .= '<tr><td>';
            $html .= implode('</td><td>', array_map('htmlentities', $log));
            $html .= '</td></tr>';
        }
        return '<div class="logs"><h2>Logs</h2><table>'.$html.'</table></div>';
    }
}
