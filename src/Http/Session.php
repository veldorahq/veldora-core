<?php

declare(strict_types=1);

namespace Veldora\Framework\Http;

/**
 * Session — a thin, framework-integrated session wrapper.
 *
 * Manages PHP's native session with added conveniences:
 *   - Flash data (persists for exactly one request)
 *   - Old input storage (for re-populating forms after validation failure)
 *   - Token-based CSRF protection
 *   - Dot-notation access for nested values
 *
 * Usage:
 *   $session = new Session();
 *   $session->start();
 *
 *   $session->put('user_id', 42);
 *   $session->get('user_id');          // 42
 *   $session->flash('success', 'Saved!');
 *   $session->get('success');          // available this request only
 *   $session->token();                 // CSRF token
 */
class Session
{
    /** Key under which flash data is stored. */
    protected const FLASH_KEY = '_flash';

    /** Key under which old input is stored. */
    protected const OLD_KEY = '_old_input';

    /** Key under which the CSRF token is stored. */
    protected const TOKEN_KEY = '_token';

    /** Whether the session has been started. */
    protected bool $started = false;

    // -------------------------------------------------------------------------
    // Lifecycle
    // -------------------------------------------------------------------------

    /**
     * Start the session if it hasn't been started already.
     */
    public function start(): bool
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            $this->ageFlashData();
            return true;
        }

        $result = session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
        ]);

        if ($result) {
            $this->started = true;
            $this->ageFlashData();
        }

        return $result;
    }

    /**
     * Check whether the session is active.
     */
    public function isStarted(): bool
    {
        return $this->started || session_status() === PHP_SESSION_ACTIVE;
    }

    /**
     * Destroy the entire session and clear the cookie.
     */
    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->started = false;
    }

    /**
     * Regenerate the session ID to prevent session fixation attacks.
     */
    public function regenerate(bool $deleteOldSession = true): bool
    {
        return session_regenerate_id($deleteOldSession);
    }

    // -------------------------------------------------------------------------
    // Read / Write
    // -------------------------------------------------------------------------

    /**
     * Store a value in the session.
     * Supports dot-notation: put('user.name', 'Alice')
     */
    public function put(string $key, mixed $value): void
    {
        $this->setNested($_SESSION, $key, $value);
    }

    /**
     * Retrieve a value from the session.
     * Supports dot-notation: get('user.name')
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Check flash first (current-request flash)
        $flash = $_SESSION[self::FLASH_KEY]['current'] ?? [];
        if (array_key_exists($key, $flash)) {
            return $flash[$key];
        }

        return $this->getNested($_SESSION, $key, $default);
    }

    /**
     * Determine if a key exists in the session.
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Remove a key from the session.
     */
    public function forget(string $key): void
    {
        $this->unsetNested($_SESSION, $key);
    }

    /**
     * Remove all data from the session (except flash/token infrastructure).
     */
    public function flush(): void
    {
        $flash = $_SESSION[self::FLASH_KEY] ?? [];
        $token = $_SESSION[self::TOKEN_KEY] ?? null;

        $_SESSION = [];

        if ($flash) {
            $_SESSION[self::FLASH_KEY] = $flash;
        }
        if ($token !== null) {
            $_SESSION[self::TOKEN_KEY] = $token;
        }
    }

    /**
     * Retrieve a value and immediately remove it from the session.
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        $value = $this->get($key, $default);
        $this->forget($key);
        return $value;
    }

    /**
     * Retrieve all session data.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $_SESSION ?? [];
    }

    // -------------------------------------------------------------------------
    // Flash Data
    // -------------------------------------------------------------------------

    /**
     * Flash a key-value pair for the *next* request only.
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION[self::FLASH_KEY]['next'][$key] = $value;
    }

    /**
     * Flash the current input array so forms can be re-populated.
     *
     * @param array<string, mixed> $input
     */
    public function flashInput(array $input): void
    {
        $this->flash(self::OLD_KEY, $input);
    }

    /**
     * Retrieve "old" (previously flashed) input value.
     */
    public function old(string $key, mixed $default = null): mixed
    {
        $old = $this->get(self::OLD_KEY, []);
        return is_array($old) ? ($old[$key] ?? $default) : $default;
    }

    /**
     * Reflash all current flash data to the next request.
     */
    public function reflash(): void
    {
        $current = $_SESSION[self::FLASH_KEY]['current'] ?? [];
        foreach ($current as $key => $value) {
            $_SESSION[self::FLASH_KEY]['next'][$key] = $value;
        }
    }

    /**
     * Move next-request flash data into current-request flash data.
     * Called automatically on start().
     */
    protected function ageFlashData(): void
    {
        $next = $_SESSION[self::FLASH_KEY]['next'] ?? [];
        $_SESSION[self::FLASH_KEY]['current'] = $next;
        $_SESSION[self::FLASH_KEY]['next']    = [];
    }

    // -------------------------------------------------------------------------
    // CSRF Token
    // -------------------------------------------------------------------------

    /**
     * Get the current CSRF token, generating one if it doesn't exist.
     */
    public function token(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Regenerate the CSRF token.
     */
    public function regenerateToken(): string
    {
        $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Verify a CSRF token against the stored token.
     */
    public function verifyToken(string $token): bool
    {
        return hash_equals($this->token(), $token);
    }

    // -------------------------------------------------------------------------
    // Dot-notation helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $array
     */
    protected function getNested(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * @param array<string, mixed> $array
     */
    protected function setNested(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $ref  = &$array;

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $ref[$segment] = $value;
                return;
            }
            if (!isset($ref[$segment]) || !is_array($ref[$segment])) {
                $ref[$segment] = [];
            }
            $ref = &$ref[$segment];
        }
    }

    /**
     * @param array<string, mixed> $array
     */
    protected function unsetNested(array &$array, string $key): void
    {
        $keys  = explode('.', $key);
        $last  = array_pop($keys);
        $ref   = &$array;

        foreach ($keys as $segment) {
            if (!is_array($ref) || !array_key_exists($segment, $ref)) {
                return;
            }
            $ref = &$ref[$segment];
        }

        unset($ref[$last]);
    }
}
