<?php
/**
 * p01-contact for Pico CMS - Simply add contact forms in your pages
 *
 * This plugin let you add contact forms in your pages by writing simple tags.
 * You can also define recipients or create your own complex forms.
 *
 * This file is the handle of p01-contact for Pico CMS.
 *
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 * @package p01-contact
 * @version 1.0.0
 */

require_once 'src/P01contact.php';

class P01contact_pico extends AbstractPicoPlugin
{
    private $P01contact;

    /**
     * Triggered after Pico has read its configuration
     * 
     * Initialize P01contact and get default language in settings
     *
     * @see    Pico::getConfig()
     * @param  array &$config array of config variables
     * @return void
     */
     public function onConfigLoaded(array &$config)
     {
        $this->P01contact = new P01C\P01contact();
        if(!empty($config['default_language'])) {
            $this->P01contact->default_lang = $config['default_language'];
        }
    }
    /**
     * Parse pages content
     */
    public function onContentPrepared(&$content)
    {
        global $p01contact;
    
        $content = $this->P01contact->parse($content);
    
        if ($this->P01contact->config('debug')) {
            $this->P01contact->debug();
        }
    }
}
