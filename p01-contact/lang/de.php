<?php
/**
 * German language file
 * @author Martin Köhler
 * @package p01-contact
 */
$p01contact_lang = array(
// fields
'name'    => 'Name',
'email'   => 'E-Mail',
'address' => 'Anschrift',
'phone'   => 'Telefonnummer',
'website' => 'Website',
'subject' => 'Betreff',
'message' => 'Nachricht',
'file'    => 'Anhang',
'captcha' => 'Captcha',
'reload'  => 'Neuladen',
'fieldcaptcha' => 'Folgendes Feld <b>NICHT</b> beschriften :',
'askcopy' => 'Eine Kopie an mich senden',
'send'    => 'Senden',

// email words
'askedcopy'=> 'Eine Kopie wurde angefordert',
'nofrom'   => 'Anonym',
'nosubject'=> '(kein Betreff)',
'fromsite' => 'Nachricht gesendet von',
'sentfrom' => 'Diese Nachricht wurde gesendet von der Seite',
        
// status messages
'sent'    => 'Nachricht verschickt.',
'error'   => 'Fehler : Nachricht wurde nicht verschickt.',
'disable' => 'Konaktformular deaktiviert.',
'target'  => 'Dieser Kontakt hat keine E-Mail-Adresse.',
'token'   => 'Die Nachricht wurde bereits verschickt.',

// fields errors
'field_required'=> 'Dieses Feld ist obligatorisch',
'field_email'   => 'Bitte korrekte E-Mail-Adresse verwenden',
'field_phone'   => 'Bitte korrekte Telefonnummer verwenden',
'field_website' => 'Bitte korrekte Internetadresse angeben',
'field_message' => 'Bitte umfangreichere Nachricht schreiben',
'field_captcha' => 'Folgendes bitte abschreiben',
'field_fieldcaptcha' => 'Folgendes nicht beschriften',
'field_password'=> 'Passwort falsch',

// configuration panel

'config_title' => 'p01contact Konfiguration',

// messages
'config_updated' => 'Erfolgreich eingestellt.',

'config_error_open' =>
'<b>Konfigurationsdatei nicht lesbar.</b> 
Datei eventuell anlegen und Rechte einstellen :',

'config_error_modify' => 
'<b>Konfigurationsdatei nicht beschreibbar.</b> 
Rechte einstellen :',

// New release alert
'new_release' => 'Es gibt eine neue Version!',
'download' => 'Aktuelle Version herunterladen',

// Links
'doc' => 'Dokumentation',
'forum' => 'Forum',

// Parameters
'enable'     => 'Aktivieren',
'enable_sub' =>
'E-Mail-Versand ein- oder ausschalten.',

'default_email'     => 'Standard-E-Mail-Adresse',
'default_email_sub' => 'Leerlassen um einzustellen',

'lang'     => 'Sprache',
'lang_sub' => 'Standardsprache ist',

'default_params'     => 'Standardwerte',
'default_params_sub' =>
'Standardstruktur. Bitte Syntax laut Dokumentation verwenden.',

'message_len'     => 'Mindestzeichenzahl',
'message_len_sub' => 'Mindestanzahl an Zeichen im Nachrichtenfeld.',

'checklists'     => 'Feldercheckliste',
'blacklist'      => 'Blacklist',
'whitelist'      => 'Whitelist',
'checklists_sub' =>
'Blacklist : Verbotene Werte.<br />
Whitelist : Erlaubte Werte.<br />
Durch Kommata abgetrennt.',

'general_fields' => 'Allgemeine Felder',
'special_fields' => 'Besondere Felder',

'debug'     => 'Debugmodus',
'debug_sub' =>
'E-Mail versand deaktiveren, Datenstruktur von p01-contact anzeigen, Daten per POST verschickt und 
die E-Mail, die verschickt worden waere.',
'debug_warn'=> 'Nicht im laufenden Betrieb verwenden!'
);
?>
