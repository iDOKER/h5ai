<?php

class Api {
    private $context;
    private $request;
    private $setup;

    public function __construct($context) {
        $this->context = $context;
        $this->request = $context->get_request();
        $this->setup = $context->get_setup();
    }

    public function apply() {
        $action = $this->request->query('action');
        $supported = ['download', 'get', 'login', 'logout'];
        Util::json_fail(Util::ERR_UNSUPPORTED, 'unsupported action', !in_array($action, $supported));

        // CSRF protection: validate Origin header for sensitive operations
        // Skip validation for login (entry point) and get (read-only operations)
        if (in_array($action, ['download', 'logout'])) {
            $this->validate_csrf();
        }

        $methodname = 'on_' . $action;
        $this->$methodname();
    }

    /**
     * Validate CSRF protection using Origin header
     * This prevents cross-site request forgery attacks
     */
    private function validate_csrf() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        // Build expected origin from current request
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
        $scheme = $is_https ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'];
        $expected_origin = $scheme . $host;

        // Check Origin header first (more reliable)
        if ($origin !== null) {
            if ($origin !== $expected_origin) {
                Util::json_fail(Util::ERR_UNSUPPORTED, 'CSRF validation failed: invalid origin', true);
            }
            return; // Origin is valid
        }

        // Fall back to Referer header if Origin is not present
        if ($referer !== null) {
            $referer_host = parse_url($referer, PHP_URL_HOST);
            $referer_scheme = parse_url($referer, PHP_URL_SCHEME);
            $referer_origin = $referer_scheme . '://' . $referer_host;

            if ($referer_origin !== $expected_origin) {
                Util::json_fail(Util::ERR_UNSUPPORTED, 'CSRF validation failed: invalid referer', true);
            }
            return; // Referer is valid
        }

        // If neither Origin nor Referer is present, allow the request
        // (some browsers/proxies strip these headers)
        // The SameSite cookie attribute provides additional protection
    }

    private function on_download() {
        Util::json_fail(Util::ERR_DISABLED, 'download disabled', !$this->context->query_option('download.enabled', false));

        $as = $this->request->query('as');
        $type = $this->request->query('type');
        $base_href = $this->request->query('baseHref');
        $hrefs = $this->request->query('hrefs', '');

        $archive = new Archive($this->context);

        // Sanitize filename: remove CRLF, quotes, and other control characters to prevent header injection
        $safe_as = preg_replace('/[\r\n"\\\\\x00-\x1f\x7f]/', '', $as);
        if (empty($safe_as)) {
            $safe_as = 'download';
        }

        set_time_limit(0);
        session_write_close();
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $safe_as . '"');
        header('Connection: close');
        $ok = $archive->output($type, $base_href, $hrefs);

        Util::json_fail(Util::ERR_FAILED, 'packaging failed', !$ok);
        exit;
    }

    private function on_get() {
        $response = [];

        foreach (['langs', 'options', 'types'] as $name) {
            if ($this->request->query_boolean($name, false)) {
                $methodname = 'get_' . $name;
                $response[$name] = $this->context->$methodname();
            }
        }

        if ($this->request->query_boolean('setup', false)) {
            // Only return setup info to authenticated admins
            Util::json_fail(Util::ERR_UNSUPPORTED, 'admin access required', !$this->context->is_admin());
            // Log admin setup info requests for audit trail
            error_log('[h5ai] Admin setup info requested from IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            $response['setup'] = $this->setup->to_jsono(true);
        }

        if ($this->request->query_boolean('theme', false)) {
            $theme = new Theme($this->context);
            $response['theme'] = $theme->get_icons();
        }

        if ($this->request->query('items', false)) {
            $href = $this->request->query('items.href');
            $what = $this->request->query_numeric('items.what');
            $response['items'] = $this->context->get_items($href, $what);
        }

        if ($this->request->query('custom', false)) {
            Util::json_fail(Util::ERR_DISABLED, 'custom disabled', !$this->context->query_option('custom.enabled', false));
            $href = $this->request->query('custom');
            $custom = new Custom($this->context);
            $response['custom'] = $custom->get_customizations($href);
        }

        if ($this->request->query('l10n', false)) {
            Util::json_fail(Util::ERR_DISABLED, 'l10n disabled', !$this->context->query_option('l10n.enabled', false));
            $iso_codes = $this->request->query_array('l10n');
            $iso_codes = array_filter($iso_codes);
            $response['l10n'] = $this->context->get_l10n($iso_codes);
        }

        if ($this->request->query('search', false)) {
            Util::json_fail(Util::ERR_DISABLED, 'search disabled', !$this->context->query_option('search.enabled', false));
            $href = $this->request->query('search.href');
            $pattern = $this->request->query('search.pattern');
            $ignorecase = $this->request->query_boolean('search.ignorecase', false);
            $search = new Search($this->context);
            $response['search'] = $search->get_items($href, $pattern, $ignorecase);
        }

        if ($this->request->query('thumbs', false)) {
            Util::json_fail(Util::ERR_DISABLED, 'thumbnails disabled', !$this->context->query_option('thumbnails.enabled', false));
            Util::json_fail(Util::ERR_UNSUPPORTED, 'thumbnails not supported', !$this->setup->get('HAS_PHP_JPEG'));
            $thumbs = $this->request->query_array('thumbs');
            $response['thumbs'] = $this->context->get_thumbs($thumbs);
        }

        Util::json_exit($response);
    }

    private function on_login() {
        // Rate limiting: prevent brute force attacks
        $ip = $_SERVER['REMOTE_ADDR'];
        $rate_key = '__H5AI__LOGIN_RATE_' . md5($ip);

        // Get current rate limiting data from session
        $rate_data = $_SESSION[$rate_key] ?? ['count' => 0, 'first_attempt' => time(), 'locked_until' => 0];

        // Check if IP is locked out
        if ($rate_data['locked_until'] > time()) {
            $wait_seconds = $rate_data['locked_until'] - time();
            Util::json_fail(Util::ERR_FAILED, 'Too many login attempts. Please wait ' . $wait_seconds . ' seconds.', true);
        }

        // Reset counter if lockout period has passed
        if ($rate_data['locked_until'] > 0 && $rate_data['locked_until'] <= time()) {
            $rate_data = ['count' => 0, 'first_attempt' => time(), 'locked_until' => 0];
        }

        // Check if we're in a new time window (5 minutes)
        if (time() - $rate_data['first_attempt'] > 300) {
            $rate_data = ['count' => 0, 'first_attempt' => time(), 'locked_until' => 0];
        }

        // Attempt login
        $pass = $this->request->query('pass');
        $result = $this->context->login_admin($pass);

        if (!$result) {
            // Failed login attempt
            $rate_data['count']++;

            // Lock out after 5 failed attempts for 15 minutes
            if ($rate_data['count'] >= 5) {
                $rate_data['locked_until'] = time() + 900; // 15 minutes
                $_SESSION[$rate_key] = $rate_data;
                Util::json_fail(Util::ERR_FAILED, 'Too many failed login attempts. Locked out for 15 minutes.', true);
            }

            $_SESSION[$rate_key] = $rate_data;
        } else {
            // Successful login - clear rate limiting data
            unset($_SESSION[$rate_key]);
        }

        Util::json_exit(['asAdmin' => $result]);
    }

    private function on_logout() {
        Util::json_exit(['asAdmin' => $this->context->logout_admin()]);
    }
}
