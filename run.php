<?php

/**
 * Some useful PHP settings
 */
ini_set('display_errors','On');             // PHP_INI_ALL
ini_set('display_startup_errors','On');     // PHP_INI_ALL
date_default_timezone_set('Europe/Vilnius');

/**
 * Class autoloading
 */
spl_autoload_register(function (string $className) {
    require __DIR__.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
});

/**
 * Error and Exception handling
 */
error_reporting(E_ALL ^ E_WARNING);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');


App\Command::execute(getopt('', ['command:', 'name:', 'email:', 'phone:', 'address:', 'date:', 'id:', 'file:', 'from:', 'to:']));
