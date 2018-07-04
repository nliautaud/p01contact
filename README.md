# p01contact

[![](https://img.shields.io/github/release/nliautaud/p01contact.svg?style=for-the-badge&label=Latest+release)](https://github.com/nliautaud/p01contact/releases)

Create contact forms by writing simple tags.

- Supports any fields types
- Powerful [textual syntax][syntax]
- Generates [comprehensive mails][emails]
- UTF-8, [localized and multilingual][i18n]
- Automatic [security measures]
- Integrated [settings editor][settings]
- Debug reports and [submission logs][logs]
- Plugin for [GetSimple][GetSimple plugin] or [Pico CMS][Pico CMS plugin]
 
<p align="center">
<img src="images/capture.png"><img src="images/capture_complex.png">
</p>

## Installation

Download the files.

For [GetSimple CMS], place the `p01-contact` directory and the file `p01-contact_gs.php` in `plugins/`.

For [Pico CMS], place the `p01-contact` directory in `plugins/` and **rename-it `PicoContact`**.

*Compatibility : PHP 5.4+*

## Usage as a plugin

Just write tags in your pages.

```
This is a default contact form :

(% contact %)

Simple.
```

Follow the [syntax] to create custom forms.

```
(% contact en :
    subject => A locked subject,
    radio "I'd like to contact you" = a little | a lot |: passionately,
    select "Department" (the floor you look for) = Silly walks :| Strange things,
    email!,
    message =< Bla bla placeholder,
    checkbox! "I'm in control",
    askcopy
%)
```

Details about usage as a plugin can be found in the [wiki] :
- [GetSimple plugin](https://github.com/nliautaud/p01contact/wiki/GetSimple-plugin)
- [Pico CMS plugin](https://github.com/nliautaud/p01contact/wiki/Pico-CMS-plugin)

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
[GetSimple plugin]: https://github.com/nliautaud/p01contact/wiki/GetSimple-plugin
[Pico CMS plugin]: https://github.com/nliautaud/p01contact/wiki/Pico-CMS-plugin
[wiki]: https://github.com/nliautaud/p01contact/wiki/_pages
[syntax]: https://github.com/nliautaud/p01contact/wiki/Syntax
[settings]: https://github.com/nliautaud/p01contact/wiki/Settings
[security measures]: https://github.com/nliautaud/p01contact/wiki/Settings#security
[i18n]: https://github.com/nliautaud/p01contact/wiki/Localization-(i18n)
[emails]: https://github.com/nliautaud/p01contact/wiki/Emails
[logs]: https://github.com/nliautaud/p01contact/wiki/Logs
