<?php

class Logger {
    private static $start;
    private static $prev;

    private static function time() {
        return microtime(true) * 1000; // sec * 1000 = ms
    }

    public static function init() {
        self::$start = self::time();
        self::$prev = self::$start;
        register_shutdown_function(function () { Logger::log('shutdown'); });
        Logger::log('--------------------------------');
    }

    public static function log($message=null, $obj=null) {
        $now = self::time();
        $message = number_format($now - self::$start, 3) . ' ' . number_format($now - self::$prev, 3) . ' ' . $message;

        // Sanitize sensitive data before logging
        $sanitized_obj = self::sanitize_for_log($obj);
        @error_log($message . ' ' . var_export($sanitized_obj, true));

        self::$prev = $now;
    }

    /**
     * Sanitize sensitive data to prevent information leakage in logs
     */
    private static function sanitize_for_log($data) {
        if (!is_array($data)) {
            // Mask potential passwords, tokens, and sensitive strings
            if (is_string($data) && preg_match('/pass|token|secret|key|auth/i', $data)) {
                return '***REDACTED***';
            }
            return $data;
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            // Mask sensitive keys
            if (preg_match('/pass|token|secret|key|auth|session/i', $key)) {
                $sanitized[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitize_for_log($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}

Logger::init();
