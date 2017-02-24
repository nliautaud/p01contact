<?php
/**
 * English language file
 * @author Nicolas Liautaud
 * @package p01-contact
 */
$p01contact_lang = array(
// fields
'name'    => 'Name',
'email'   => 'Email',
'address' => 'Postal address',
'phone'   => 'Phone number',
'website' => 'Website',
'subject' => 'Subject',
'message' => 'Message',
'file'    => 'Attachment',
'captcha' => 'Captcha',
'reload'  => 'Reload',
'fieldcaptcha' => 'Please <b>don\'t</b> fill the following field :',
'askcopy' => 'Send me a copy of this email',
'send'    => 'Send',

// email words
'askedcopy'=> 'A copy of this email have been requested',
'nofrom'   => 'Anonymous',
'nosubject'=> '(No subject)',
'fromsite' => 'Mail sent from',
'sentfrom' => 'This email was sent from page',

// status messages
'sent'    => 'Email sent.',
'error'   => 'Error : no message was sent.',
'disable' => 'Contact forms are disable.',
'target'  => 'This contact form has no recipient.',
'token'   => 'The message have already been sent.',

// fields errors
'field_required'=> 'This field is required',
'field_email'   => 'Please use a valid email address',
'field_phone'   => 'Please use a valid phone number',
'field_website' => 'Please write a valid web address',
'field_message' => 'Please write a longer message',
'field_captcha' => 'Please answer correctly the following captcha',
'field_fieldcaptcha' => 'Please don\'t fill this field',
'field_password'=> 'Wrong password',

// configuration panel

'config_title' => 'p01contact configuration',

// messages
'config_updated' => 'Your changes were saved successfully.',

'config_error_open' =>
'<b>Unable to open config file.</b>
Check if the file exists and its permissions :',

'config_error_modify' =>
'<b>Unable to modify config file.</b>
Check the file permissions :',

// New release alert
'new_release' => 'There is a new release!',
'download' => 'Download the last version',

// Links
'repo' => 'Github',
'wiki' => 'Documentation / Wiki',
'issues' => 'Help / report a bug',

// Parameters
'disable'     => 'Disable all forms',
'disable_sub' =>
'Disable mail sending, without hiding the contact forms.',

'default_email'     => 'Default emails',
'default_email_sub' => 'One ore more email addresses, separated by commas, that will receive mails from every forms.',

'lang'     => 'Language',
'lang_sub' => 'Default language is set to',

'message_len'     => 'Messages minimum length',
'message_len_sub' => 'Minimum number of characters for message fields.',

'default_params'     => 'Default parameters',
'default_params_sub' => 'Default form structure. Use syntax described in documentation.',

'separator'     => 'Separator',
'separator_sub' => 'Parameters separator in forms markup. Ex: comma, semicolon, double-pipe...',

'recaptcha_public_key' => 'reCaptcha public key',
'recaptcha_public_key_sub' => 'Google reCaptcha public key. See <a href="https://www.google.com/recaptcha/admin">reCaptcha admin</a>.',
'recaptcha_secret_key' => 'reCaptcha secret key',
'recaptcha_secret_key_sub' => 'Google reCaptcha secret key. See <a href="https://www.google.com/recaptcha/admin">reCaptcha admin</a>.',

'checklists'     => 'Fields checklists',
'blacklist'      => 'Blacklist',
'whitelist'      => 'Whitelist',
'checklists_sub' =>
'Blacklist : values that must not be present in the field to send email.<br />
Whitelist : possibles values required for the field to send email.<br />
Separated by commas.',

'general_fields' => 'General fields',
'special_fields' => 'Special fields',

'debug'     => 'Debug mode',
'debug_sub' =>
'Disable mail sending, display p01-contact data structure, data sent by POST and
the email that would have been sent.',
'debug_warn'=> 'Don\'t active that on production website!'
);
?>
