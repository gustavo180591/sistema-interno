<?php

use App\Kernel;

// Set default timezone
date_default_timezone_set('America/Argentina/Buenos_Aires');

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
