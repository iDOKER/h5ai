<?php

define('H5AI_VERSION', '{{VERSION}}');
define('MIN_PHP_VERSION', '7.0.0');

if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<')) {
    header('Content-type: text/plain;charset=utf-8');
    // Log detailed error for debugging, show generic message to users
    error_log('[h5ai] PHP version check failed: required ' . MIN_PHP_VERSION . ', found ' . PHP_VERSION);
    exit('[ERR] h5ai requires a newer PHP version. Please contact your system administrator.');
}

if (substr(H5AI_VERSION, 0, 1) === '{') {
    header('Content-type: text/plain;charset=utf-8');
    // Log detailed error for debugging, show generic message to users
    error_log('[h5ai] Version placeholder not replaced - sources not preprocessed');
    exit('[ERR] h5ai installation error. Please contact your system administrator.');
}

require_once __DIR__ . '/../private/php/class-bootstrap.php';
Bootstrap::run();
