<?php
require_once __DIR__ . '/src/P01contact.php';

if(class_exists('AbstractPicoPlugin')) {
    require_once 'handles/Pico.php';
} elseif(defined('GSPLUGINPATH')) {
    require_once 'handles/GetSimple.php';
}