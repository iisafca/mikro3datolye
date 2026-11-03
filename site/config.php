<?php
session_start();
$env = @parse_ini_file(dirname(__DIR__) . '/mikro3datolye.env', false, INI_SCANNER_RAW) ?: [];
try {
  $db = new PDO("mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4", $env['DB_USER'], $env['DB_PASS'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
} catch (Throwable $e) { http_response_code(500); exit('Veritabanına bağlanılamadı. mikro3datolye.env dosyasını kontrol edin.'); }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function tl($n){ return number_format((float)$n, 2, ',', '.') . ' ₺'; }
