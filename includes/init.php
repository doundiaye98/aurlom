<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/config.php';
require_once __DIR__ . '/schema.php';
aurloum_migrate();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
