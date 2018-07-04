<?php
/**
 * p01-contact for Pico CMS - Simply add contact forms in your pages
 *
 * This plugin let you add contact forms in your pages by writing simple tags.
 * You can also define recipients or create your own complex forms.
 *
 * This file is the handle of p01-contact for Pico 2.
 *
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 * @package p01contact
 * @version 1.1
 */

require_once 'src/P01contact.php';

class PicoContact extends AbstractPicoPlugin
{
    const API_VERSION = 2;

    private $P01contact;

    /**
     * Initialize P01contact and set the default language from Pico settings
     *
     * Triggered after Pico has read its configuration
     *
     * @see    Pico::getConfig()
     * @param  array &$config array of config variables
     * @return void
     */
    public function onConfigLoaded(array &$config)
    {
        $this->P01contact = new P01C\P01contact();

        if (!empty($config['default_language'])) {
            $this->P01contact->default_lang = $config['default_language'];
        }
    }
    /**
     * Replace (% contact %) tags and contact_admin tags in pages content
     *
     * Triggered after Pico has prepared the raw file contents for parsing
     *
     * @see    Pico::parseFileContent()
     * @see    DummyPlugin::onContentParsed()
     * @param  string &$content prepared file contents for parsing
     * @return void
     */
    public function onContentPrepared(&$content)
    {
        // replace config panel (% contact_admin_config %)
        $content = preg_replace_callback('`\(%\s*contact_admin_config\s*%\)`', function () {
            return '<div>' . $this->P01contact->panel(). '</div>';
        }, $content, 1);

        // replace debug report (% contact_admin_debug %)
        $content = preg_replace_callback('`\(%\s*contact_admin_debug\s*%\)`', function () {
            if (!$this->P01contact->config('debug')) {
                return '';
            }
            return '<div>' . $this->P01contact->debugReport() .'</div>';
        }, $content, 1);

        // replace forms (% contact ... %)
        $content = $this->P01contact->parse($content);
    }
    /**
     * Add  {{ contact() }}  and  {{ contact_admin() }}  twig functions
     * For outputing forms and admin panels from themes templates
     * 
     * Triggered when Pico registers the twig template engine
     *
     * @see Pico::getTwig()
     * @param Twig_Environment &$twig Twig instance
     * @return void
     */
    public function onTwigRegistered(Twig_Environment &$twig)
    {
        // {{ contact() }}                   output the default form
        // {{ contact('parameters') }}       custom parameters
        // {{ contact('fr', 'parameters') }} custom parameters and form-specific language
        // {{ contact('fr', null) }}         default form with form-specific language
        $twig->addFunction(new Twig_SimpleFunction('contact', function ($a = null, $b = null) {
            if ($b) {
                return $this->P01contact->newForm($b, $a);
            }
            return $this->P01contact->newForm($a);
        }));

        // {{ contact_admin('debug') }}       output the debug report
        // {{ contact_admin('config') }}      output the config panel
        $twig->addFunction(new Twig_SimpleFunction('contact_admin', function ($type) {
            if ($type == 'debug' && $this->P01contact->config('debug')) {
                return $this->P01contact->debugReport();
            }
            if ($type == 'config') {
                return $this->P01contact->panel();
            }
        }));
    }
}
