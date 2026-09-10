<?php

// Local-only runtime: never loads the shop's .env or its configuration cache.
require_once __DIR__.'/../vendor/autoload.php';
$stageRoot = dirname(__DIR__).'/storage/app/local-staging';
if (!is_file($stageRoot.'/key')) throw new RuntimeException('Run php scripts/staging-setup.php first.');
$stageEnv = [
    'APP_ENV'=>'staging', 'APP_DEBUG'=>'false', 'APP_NAME'=>'Mizuki STAGING',
    'APP_KEY'=>trim(file_get_contents($stageRoot.'/key')), 'APP_URL'=>'http://127.0.0.1:8010',
    'APP_CONFIG_CACHE'=>$stageRoot.'/cache/config.php', 'APP_ROUTES_CACHE'=>$stageRoot.'/cache/routes.php',
    'APP_EVENTS_CACHE'=>$stageRoot.'/cache/events.php',
    'DB_CONNECTION'=>'sqlite', 'DB_DATABASE'=>$stageRoot.'/database.sqlite', 'DB_URL'=>'',
    'CACHE_STORE'=>'file', 'SESSION_DRIVER'=>'file', 'SESSION_COOKIE'=>'mizuki_local_staging',
    'SESSION_SECURE_COOKIE'=>'false', 'SESSION_DOMAIN'=>'', 'MAIL_MAILER'=>'log',
    'QUEUE_CONNECTION'=>'sync', 'BROADCAST_CONNECTION'=>'log', 'LARAVEL_STORAGE_PATH'=>$stageRoot.'/storage',
    'PROMPTPAY_QR_IMAGE'=>'', 'TRUEMONEY_QR_IMAGE'=>'',
];
foreach ($stageEnv as $key=>$value) { $_ENV[$key] = $_SERVER[$key] = $value; putenv($key.'='.$value); }
$app = require __DIR__.'/../bootstrap/app.php';
$app->loadEnvironmentFrom('.env.staging-not-used');
$app->usePublicPath($stageRoot.'/public');
return $app;
