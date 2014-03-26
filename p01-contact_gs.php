<?php
/**
 * p01-contact for GetSimple - Simply add contact forms in your pages
 *
 * This plugin let you add contact forms in your pages by writing simple tags.
 * You can also define recipients or create your own complex forms.
 *
 * This file is the handle of p01-contact for GetSimple.
 *
 * @link http://nliautaud.fr/wiki/travaux/getsimple_p01-contact Documentation
 * @link http://get-simple.info/extend/plugin/p01-contact/35 Latest Version
 * @author Nicolas Liautaud <contact@nliautaud.fr>
 * @package p01-contact
 * @version 0.9.1
 */

require_once GSPLUGINPATH . 'p01-contact/p01-contact.php';
$p01contact = new P01contact();
$p01contact->default_email = admin_email();
$p01contact->default_lang = substr($LANG, 0, 2);
$p01contact->securimage_url = $SITEURL . 'plugins/p01-contact/captcha/';

$thisfile = basename(__FILE__, '.php');

register_plugin(
	$thisfile, 	            // ID of plugin, should be filename minus php
	'p01-contact', 	        // Title of plugin
	$p01contact->version,   // Version of plugin
	'Nicolas Liautaud',	    // Author of plugin
	'http://nliautaud.fr',  // Author URL
	'Simply add contact forms in your pages', // Plugin Description
	'plugins', 	            // Page type of plugin
	'p01contact_action'     // Function that displays content
);

add_filter('content','p01contact_filter');
add_action('plugins-sidebar','createSideMenu',array($thisfile,'p01-contact'));

/*
 * Handle for GS content filter (parse GS pages)
 */
function p01contact_filter($contents)
{
    global $p01contact;
    
    $contents = $p01contact->parse($contents);
    
    if($p01contact->settings('debug')) {
        $p01contact->debug();
    }
    return $contents;
}
/*
 * Handle for GS action (display admin panel)
 */
function p01contact_action()
{
    global $p01contact;
    echo $p01contact->panel();
}

/*
 * Return GS admin email
 * @return string
 */
function admin_email()
{
    $data = getXML(GSDATAOTHERPATH . 'user.xml');
    return $data->EMAIL;
}
?>
