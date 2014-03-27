<?php
/**
* Italian language file
* @author Nicola Sarti
* @package p01-contact
*/
$p01contact_lang = array(
// fields
'name'    => 'Nome',
'email'   => 'Email',
'address' => 'Indirizzo postale',
'phone'   => 'Telefono',
'website' => 'Sito web',
'subject' => 'Soggetto',
'message' => 'Messaggio',
'file'    => 'Allegato',
'captcha' => 'Captcha',
'reload'  => 'Ricarica',
'fieldcaptcha' => 'Per favore <b>non</b> compilare questo campo :',
'askcopy' => 'Invia anche a me una copia',
'send'    => 'Invia',

// email words
'askedcopy'=> 'È stata richiesta una copia di questa email',
'nofrom'   => 'Anonimo',
'nosubject'=> '(Nessun oggetto)',
'fromsite' => 'Inviata da',
'sentfrom' => 'Questa email è stata inviata dalla pagina',

// status messages
'sent'    => 'Email inviata.',
'error'   => 'Nessun messaggio inviato.',
'disable' => 'Contact plugin disabilitato.',
'target'  => 'Nessun destinatario indicato.',
'token'   => 'Il messaggio è già stato inviato.',

// fields errors
'field_required'=> 'Campo richiesto',
'field_email'   => 'Per favore usa un indirizzo email valido',
'field_phone'   => 'Per favore usa un numero di telefono valido',
'field_website' => 'Per favore usa un indirizzo web valido',
'field_message' => 'Per favore inserisci un maggior numero di caratteri',
'field_captcha' => 'Per favore copia questo testo'
'field_fieldcaptcha' => 'Per favore non compilare questo campo',
'field_password'=> 'Password errata',

// configuration panel

'config_title' => 'Configurazione p01contact',

// messages
'config_updated' => 'Le modifiche sono state applicate.',

'config_error_open' =>
'<b>Impossibile aprire il file di configurazione.</b> 
Controlla che esista e i suoi permessi :',

'config_error_modify' => 
'<b>Impossibile modificare il file di configurazione.</b> 
Controlla i permessi di accesso :',

// New release alert
'new_release' => 'È disponibile una nuova release!',
'download' => 'Scarica l\'ultima versione',

// Links
'doc' => 'Documentazione',
'forum' => 'Forum',

'default' => 'Default',
'save' => 'Salva',

// Parameters
'enable'     => 'Abilita',
'enable_sub' =>
'Abilia o disabilita l\'invio di mail (non nasconde il form per il contatto).',

'default_email'     => 'Email di default',
'default_email_sub' => 'Non compilare per lasciarlo impostato su',

'lang'     => 'Lingua',
'lang_sub' => 'La lingua di default è impostata in',

'default_params'     => 'Parametri di default',
'default_params_sub' =>
'Default tag form. Usa la sintassi descritta nella documentazione.',

'message_len'     => 'Lunghezza minima del messaggio',
'message_len_sub' => 'Numero minimo di caratteri per il campo messaggio.',

'checklists'     => 'Checklist dei campi',
'blacklist'      => 'Blacklist',
'whitelist'      => 'Whitelist',
'checklists_sub' =>
'Blacklist : valori che non devono essere presenti nel campo per inviare l\'email.<br />
Whitelist : possibili valori richiesti nel campo per inviare l\'email.<br />
Separati da virgola.',

'general_fields' => 'Campi generali',
'special_fields' => 'Campi speciali',

'debug'     => 'Debug mode',
'debug_sub' =>
'Disabilita l\'invio di email, mostra la data structure di p01-contact , i dati inviati da POST e l\'email che sarebbe stata inviata.',
'debug_warn'=> 'Non attivarlo sul website di produzione!'
);
?>
