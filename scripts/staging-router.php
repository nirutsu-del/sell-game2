<?php
// Start ONLY with: php -S 127.0.0.1:8010 scripts/staging-router.php
if (PHP_SAPI !== 'cli-server' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) {
    http_response_code(403); exit;
}
$root = dirname(__DIR__).'/storage/app/local-staging';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if (preg_match('~(?:^|/)\.|^/(?:scripts|app|bootstrap|config|database|docs|resources|routes|vendor|tests)(?:/|$)~i', $path)) {
    http_response_code(404); exit;
}
foreach (['/build/'=>$root.'/public/build', '/storage/'=>$root.'/storage/app/public'] as $prefix=>$directory) {
    if (!str_starts_with($path, $prefix)) continue;
    $base = realpath($directory);
    $file = realpath($directory.'/'.substr($path, strlen($prefix)));
    if (!$base || !$file || !str_starts_with($file, $base.DIRECTORY_SEPARATOR) || !is_file($file)) { http_response_code(404); exit; }
    $types = ['css'=>'text/css','js'=>'text/javascript','svg'=>'image/svg+xml','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp','woff2'=>'font/woff2'];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!isset($types[$extension])) { http_response_code(404); exit; }
    header('Content-Type: '.$types[$extension]); header('X-Content-Type-Options: nosniff'); readfile($file); exit;
}
$app = require __DIR__.'/staging-bootstrap.php';
$app->handleRequest(Illuminate\Http\Request::capture());
