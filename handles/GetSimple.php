<?php
/**
 * p01-contact for GetSimple - Simply add contact forms in your pages
 *
 * This plugin let you add contact forms in your pages by writing simple tags.
 * You can also define recipients or create your own complex forms.
 *
 * This file is the handle of p01-contact for GetSimple.
 *
 * @link https://github.com/nliautaud/p01contact
 * @author Nicolas Liautaud
 * @package p01-contact
 * @version 1.1
 */

$p01contact = new P01C\P01contact();
$p01contact->default_lang = substr($LANG, 0, 2);

$thisfile = basename(__FILE__, '.php');

register_plugin(
    $thisfile,              // ID of plugin, should be filename minus php
    'p01-contact',          // Title of plugin
    $p01contact->version,   // Version of plugin
    'Nicolas Liautaud',     // Author of plugin
    'http://nliautaud.fr',  // Author URL
    'Simply add contact forms in your pages', // Plugin Description
    'plugins',              // Page type of plugin
    'p01contact_action'     // Function that displays content
);

add_filter('content', 'p01contact_filter');
add_action('plugins-sidebar', 'createSideMenu', array($thisfile,'p01-contact'));

/*
 * Handle for GS content filter (parse GS pages)
 */
function p01contact_filter($contents)
{
    global $p01contact;

    $contents = $p01contact->parse($contents);

    if ($p01contact->config('debug')) {
        echo $p01contact->debugReport();
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
