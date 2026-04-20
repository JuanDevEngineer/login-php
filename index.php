<?php

// 1. Cargar variables de entorno ANTES que cualquier constante o config
require_once __DIR__ . '/helpers/env.php';
loadEnv(__DIR__ . '/.env');

// 2. Configuración de errores según entorno
$appEnv = env('APP_ENV', 'production');
error_reporting(E_ALL);
ini_set('ignore_repeated_errors', '1');
ini_set('date.timezone', 'America/Bogota');
ini_set('log_errors', '1');
ini_set('display_errors', $appEnv === 'development' ? '1' : '0');

// 3. Cargar el resto de la app
require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/config/DataBase.php';
require_once __DIR__ . '/config/Constants.php';
require_once __DIR__ . '/helpers/SesionController.php';
require_once __DIR__ . '/helpers/helpers.php';
require_once __DIR__ . '/helpers/csrf.php';
require_once __DIR__ . '/libs/view.php';
require_once __DIR__ . '/controllers/MailController.php';
require_once __DIR__ . '/controllers/Controller.php';

require_once __DIR__ . '/libs/PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/libs/PHPMailer-master/src/OAuth.php';
require_once __DIR__ . '/libs/PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer-master/src/POP3.php';
require_once __DIR__ . '/libs/PHPMailer-master/src/SMTP.php';

$inicio = new Controller();
