<?php
/**
 * Czech language file
 * @author Seva
 * @package p01-contact
 */
$p01contact_lang = array(
// fields
'name'    => 'Jméno',
'email'   => 'E-mail',
'address' => 'Adresa',
'phone'   => 'Telefonní číslo',
'website' => 'Webové stránky',
'subject' => 'Předmět',
'message' => 'Text zprávy',
'file'    => 'Přílohy',
'captcha' => 'Captcha',
'reload'  => 'Načíst znovu',
'fieldcaptcha' => 'Prosím <b>ne</b>vyplňujte následující pole:',
'askcopy' => 'Zašlete mi kopii tohoto e-mailu',
'send'    => 'Odeslat',

// email words
'askedcopy'=> 'Kopie tohoto e-mailu je požadována',
'nofrom'   => 'Anonym',
'nosubject'=> '(Bez předmětu)',
'fromsite' => 'E-mail odeslán z',
'sentfrom' => 'Tento e-mail byl odeslán ze stránky',
        
// status messages
'sent'    => 'E-mail odeslán.',
'error'   => 'Chyba: zpráva nebyla odeslána.',
'disable' => 'Kontaktní formulář není k dispozici.',
'target'  => 'Tento kontaktní formulář je bez příjemce.',
'token'   => 'Tato zpráva již byla odeslána.',

// fields errors
'field_required'=> 'Musíte vyplnit toto pole',
'field_email'   => 'Prosím vložte platný e-mail',
'field_phone'   => 'Prosím vložte platné telefonní číslo',
'field_website' => 'Prosím vložte platnou webovou adresu',
'field_message' => 'Prosím napište delší zprávu',
'field_captcha' => 'Prosím přepište následující text',
'field_fieldcaptcha' => 'Prosím nevyplňujte následující pole',
'field_password'=> 'Špatné heslo',

// configuration panel

'config_title' => 'p01contact konfigurace',

// messages
'config_updated' => 'Změny byly uloženy.',

'config_error_open' =>
'<b>Nelze otevřít konfigurační soubor.</b> 
Zkontrolujte zda soubor existuje a má oprávnění:',

'config_error_modify' => 
'<b>Nelze upravit konfigurační soubor.</b> 
Zkontrolujte oprávnění souboru:',

// New release alert
'new_release' => 'Je dostupná nová verze!',
'download' => 'Stáhnout nejnovější verzi',

// Links
'doc' => 'Dokumentace',
'forum' => 'Fórum',

// Parameters
'enable'     => 'Aktivovat',
'enable_sub' =>
'Povolit/zakázat odeslání e-mailu. (Ponechá kontaktní formulář viditelný).',

'default_email'     => 'Výchozí e-mail',
'default_email_sub' => 'Ponechte prázdné, pokud chcete ponechat nenastaveno',

'lang'     => 'Jazyk',
'lang_sub' => 'Výchozí jazyk je nastaven na',

'default_params'     => 'Výchozí parametry',
'default_params_sub' =>
'Výchozí tagy formuláře. Použijte syntaxi popsanou v dokumentaci.',

'message_len'     => 'Minimální délka zprávy',
'message_len_sub' => 'Minimální počet znaku, které musí uživatel zadat do zprávy.',

'checklists'     => 'Checklisty',
'blacklist'      => 'Blacklist',
'whitelist'      => 'Whitelist',
'checklists_sub' =>
'Blacklist : hodnoty, které nesmějí obsahovat pole.<br />
Whitelist : hodnoty, které jsou vhodné pro pole.<br />
Oddělujte středníkem.',

'general_fields' => 'Hlavní pole',
'special_fields' => 'Speciální pole',

'debug'     => 'Debug mód',
'debug_sub' =>
'Vypne odesílání e-mailu a zobrazí strukturu p01-contact pluginu.',
'debug_warn'=> 'Neaktivujte na veřejně přístupné stránce!'
);
?>
