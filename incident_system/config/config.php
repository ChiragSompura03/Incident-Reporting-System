<?php

define('APP_NAME',    'Secure Incident Reporting System');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     'development');
define('BASE_URL',    'http://localhost/incident_system');

define('DB_HOST',     'localhost');
define('DB_PORT',     '3306');
define('DB_NAME',     'incident_db');
define('DB_USER',     'root');
define('DB_PASS',     '');
define('DB_CHARSET',  'utf8mb4');

define('JWT_SECRET',          'S3cur3_S3cr3t_K3y_Ch@ng3_Th1s_1n_Pr0d!');
define('JWT_ACCESS_EXPIRY',   900); // 15 minutes
define('JWT_REFRESH_EXPIRY',  604800); // 7 days
define('JWT_ALGORITHM',       'HS256');

define('UPLOAD_DIR',      __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL',      BASE_URL . '/assets/uploads/');
define('MAX_FILE_SIZE',   5 * 1024 * 1024);
define('ALLOWED_TYPES',   ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
define('ALLOWED_EXT',     ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

define('SESSION_NAME',    'incident_sess');
define('SESSION_EXPIRY',  3600);

define('RECORDS_PER_PAGE', 10);

define('ROLE_USER',       'user');
define('ROLE_ADMIN',      'admin');
define('ROLE_SUPERADMIN', 'superadmin');

if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

date_default_timezone_set('Asia/Kolkata');

if (session_status() === PHP_SESSION_NONE) {
    session_name('incident_sess');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}
