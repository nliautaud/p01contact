p01contact
==========

Create contact forms by writing simple tags.

p01-contact is natively a plugin for [GetSimple CMS](http://get-simple.info).

Live examples, syntax and settings are documented in the [Wiki](https://github.com/nliautaud/p01contact/wiki/_pages)

## Installation

Download the files.

For GetSimple CMS, unzip it in the ``plugins/`` directory.

## Use

### As a GetSimple plugin

Just write tags in your pages according to the [syntax](https://github.com/nliautaud/p01contact/wiki/Syntax). Reminds you to fill the Meta Description accessible in page options. If you don't, GetSimple will show the tag source in the source code of the output page.

```
This is a default contact form :

(% contact %)

Simple.
```

You can also use it in components or templates by manipulating the variable ``$p01contact``, already initialized. For example, to add a default contact form in your sidebar :

```php
<?php
get_component('sidebar');
echo $p01contact->parse('(% contact %)');
?>
```

### As a PHP script

Include the script, create a new instance and parse a string containing tags using the [syntax](https://github.com/nliautaud/p01contact/wiki/Syntax).

```php
include 'path/to/p01-contact/p01-contact.php';

$p01contact = new P01contact();

$content = 'This is a default contact form : (% contact %)'
$content = $p01contact->parse($content);
```
