<?php
/**
 * Database connection.
 *
 * XAMPP defaults: user "root", empty password. Import agroculture.sql
 * (phpMyAdmin > Import) to create the "kisan" database this file connects to.
 */

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'kisan';

// Show detailed error messages while developing. Set to false on a live server.
const KISAN_DEBUG = true;

// Make mysqli throw exceptions instead of failing silently.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $ex) {
    http_response_code(500);
    error_log('KISAN database connection failed: ' . $ex->getMessage());
    $detail = KISAN_DEBUG ? htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') : '';
    echo '<!DOCTYPE html><meta charset="utf-8"><title>KISAN - database unavailable</title>';
    echo '<body style="font-family:system-ui,sans-serif;max-width:640px;margin:12vh auto;padding:0 1.5rem;line-height:1.6">';
    echo '<h1>We could not reach the database</h1>';
    echo '<p>Check that <strong>MySQL</strong> is running in the XAMPP control panel and that you have imported ';
    echo '<code>agroculture.sql</code> (it creates the <code>' . DB_NAME . '</code> database).</p>';
    if ($detail !== '') {
        echo '<p style="color:#a33"><small>' . $detail . '</small></p>';
    }
    echo '</body>';
    exit;
}
