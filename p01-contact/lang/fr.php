<?php
/**
 * French language file
 * @author Nicolas Liautaud
 * @package p01-contact
 */
$p01contact_lang = array(
// fields
'name'    => 'Nom',
'email'   => 'Adresse email',
'address' => 'Adresse postale',
'phone'   => 'Numéro de téléphone',
'website' => 'Site internet',
'subject' => 'Objet',
'message' => 'Message',
'file'    => 'Pièce jointe',
'captcha' => 'Captcha',
'reload'  => 'Recharger',
'fieldcaptcha' => 'Veuillez <b>ne pas</b> remplir le champ suivant :',
'askcopy' => 'Envoyez-moi une copie de cet email',
'send'    => 'Envoyer',

// email words
'askedcopy'=> 'Une copie de cet email a été demandée',
'nofrom'   => 'Anonyme',
'nosubject'=> '(Pas d\'objet)',
'fromsite' => 'Email envoyé depuis',
'sentfrom' => 'Cet email a été envoyé depuis la page',

// status messages
'sent'    => 'Email envoyé.',
'error'   => 'Erreur : aucun message n\'a été envoyé.',
'disable' => 'Les formulaires de contact sont désactivés.',
'target'  => 'Ce formulaire de contact n\'a pas de destinataire.',
'token'   => 'Le message a déjà été envoyé.',

// fields errors
'field_required'=> 'Ce champ est obligatoire',
'field_email'   => 'Veuillez entrer une adresse email valide',
'field_phone'   => 'Veuillez entrer un numéro de téléphone valide',
'field_website' => 'Veuillez entrer une adresse internet valide',
'field_message' => 'Veuillez écrire un message plus long',
'field_captcha' => 'Veuillez recopier le texte ci-dessous',
'field_fieldcaptcha' => 'Veuillez ne pas remplir ce champ',
'field_password'=> 'Mot de passe incorrect',

// configuration panel

'config_title' => 'Configuration de p01contact',

// messages
'config_updated' => 'Vos modifications ont été enregistrées avec succès.',

'config_error_open' =>
'<b>Impossible d\'ouvrir le fichier de configuration.</b> 
Vérifiez s\'il existe et ses permissions :',

'config_error_modify' => 
'<b>Impossible de modifier le fichier de configuration.</b> 
Vérifiez ses permissions :',

// New release alert
'new_release' => 'Il y a une nouvelle version!',
'download' => 'Télécharger la dernière version',

// Links
'doc' => 'Documentation',
'forum' => 'Forum',

// Parameters
'enable'     => 'Activer',
'enable_sub' =>
'Active ou désactive l\'envoi de mail (ne cache pas les formulaires).',

'default_email'     => 'Email par défaut',
'default_email_sub' => 'Laissez-le vide afin qu\'il soit définit à ',

'lang'     => 'Langue',
'lang_sub' => 'La langue par défaut est définie à',

'default_params'     => 'Paramètres par défaut',
'default_params_sub' =>
'Structure des formulaires pour les balises par défaut.
Utilisez la syntaxe décrite dans la documentation.',

'message_len'     => 'Longueur minimum des messages',
'message_len_sub' => 'Nombre minimum de caractères authorisé pour les champs message',

'checklists'     => 'Listes de vérification des champs',
'blacklist'      => 'Liste noire',
'whitelist'      => 'Liste blanche',
'checklists_sub' =>
'Liste noire : valeurs qui ne doivent pas être présentes dans un champ pour envoyer le mail.<br />
Liste blanche : valeurs possibles devant être présentes dans un champ pour envoyer le mail.<br />
Séparées par des virgules.',

'general_fields' => 'Champs généraux',
'special_fields' => 'Champs spéciaux',

'debug'     => 'Mode debug',
'debug_sub' =>
'Désactive l\'envoi de mails, affiche la structure de données de p01-contact, 
les informations envoyées par POST et le mail qui aurait été envoyé.',
'debug_warn'=> 'A ne pas activer sur un site en production!'
);
?>
