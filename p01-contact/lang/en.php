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
'field_captcha' => 'Please copy the following text',
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
'doc' => 'Documentation',
'forum' => 'Forum',

// Parameters
'enable'     => 'Enable',
'enable_sub' =>
'Enable or disable mail sending (doesn\'t hide the contact forms).',

'default_email'     => 'Default email',
'default_email_sub' => 'Leave empty to let it set to',

'lang'     => 'Language',
'lang_sub' => 'Default language is set to',

'default_params'     => 'Default parameters',
'default_params_sub' =>
'Default tag form structrure. Use syntax described in documentation.',

'message_len'     => 'Messages minimum length',
'message_len_sub' => 'Minimum number of characters for message fields.',

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
