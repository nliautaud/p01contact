# p01contact

Create contact forms by writing simple tags.

p01-contact is natively a plugin for [GetSimple CMS] and [Pico CMS].

Live examples, syntax and settings are documented in the [wiki].

## Installation

Download the files.

For [GetSimple CMS], place the `p01-contact` directory and the file `p01-contact_gs.php` in `plugins/`.

For [Pico CMS], place the `p01-contact` directory in `plugins/`.

## Usage as a plugin

Just write tags in your pages according to the [syntax].

```
This is a default contact form :

(% contact %)

Simple.
```

For GetSimple, reminds you to fill the Meta Description accessible in page options. If you don't, GetSimple will show the tag source in the source code of the output page.

### Usage in themes

You can use it in [GetSimple CMS] components, themes or templates by manipulating the variable `$p01contact`, already initialized. For example, to add a default contact form in your sidebar :

```php
<?php
get_component('sidebar');
echo $p01contact->parse('(% contact %)');
?>
```

## Usage as a PHP script

The simplest method is to include the script, create a new instance and parse strings containing tags using the [syntax].

```php
include 'p01-contact/P01contact.php';

$p01contact = new P01contact();

$content = 'This is a default contact form : (% contact %)'
$content = $p01contact->parse($content);
```

[GetSimple CMS]: http://get-simple.info
[Pico CMS]: http://picocms.org
[wiki]: https://github.com/nliautaud/p01contact/wiki/_pages
[syntax]: https://github.com/nliautaud/p01contact/wiki/Syntax