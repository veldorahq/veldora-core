<?php

declare(strict_types=1);

namespace Veldora\Framework\Http;

/**
 * RedirectResponse — an HTTP redirect response with fluent helpers.
 *
 * Extends the base Response (302 by default) and adds:
 *  - with() / withErrors() / withInput() for session flashing
 *  - back() convenience (redirect to the Referer)
 *
 * Usage:
 *   return new RedirectResponse('/dashboard');
 *   return RedirectResponse::to('/login', 301);
 *   return RedirectResponse::back();
 *   return (new RedirectResponse('/profile'))->with('success', 'Saved!');
 *   return (new RedirectResponse('/form'))->withErrors($errors)->withInput();
 */
class RedirectResponse extends Response
{
    /**
     * Create a new RedirectResponse.
     *
     * @param array<string, string> $headers
     */
    public function __construct(
        string $url,
        int $statusCode = 302,
        array $headers = []
    ) {
        $headers['Location'] = $url;
        parent::__construct('', $statusCode, $headers);
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    /**
     * Redirect to a given URL.
     *
     * @param array<string, string> $headers
     */
    public static function to(string $url, int $statusCode = 302, array $headers = []): static
    {
        return new static($url, $statusCode, $headers);
    }

    /**
     * Redirect back to the previous page (uses HTTP Referer header).
     * Falls back to $fallback when no Referer is present.
     */
    public static function back(string $fallback = '/'): static
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? $fallback;
        return new static($referer);
    }

    /**
     * Redirect to the intended URL that was stored in the session, or $default.
     */
    public static function intended(string $default = '/'): static
    {
        $url = '/';

        if (function_exists('session') && session() !== null) {
            /** @var Session $session */
            $session = session();
            $url = $session->pull('url.intended', $default);
        }

        return new static($url);
    }

    // -------------------------------------------------------------------------
    // Session flash helpers
    // -------------------------------------------------------------------------

    /**
     * Flash a single key-value pair to the session.
     */
    public function with(string $key, mixed $value): static
    {
        if (function_exists('session') && session() !== null) {
            session()->flash($key, $value);
        }
        return $this;
    }

    /**
     * Flash a bag of validation errors to the session.
     *
     * @param array<string, string|array<string>> $errors
     */
    public function withErrors(array $errors, string $bag = 'default'): static
    {
        if (function_exists('session') && session() !== null) {
            session()->flash("errors.{$bag}", $errors);
        }
        return $this;
    }

    /**
     * Flash the current request input so forms can be re-populated.
     *
     * @param array<string, mixed>|null $input  Defaults to $_POST when null.
     */
    public function withInput(?array $input = null): static
    {
        $input ??= $_POST;

        if (function_exists('session') && session() !== null) {
            session()->flashInput($input);
        }
        return $this;
    }

    /**
     * Flash multiple key-value pairs to the session at once.
     *
     * @param array<string, mixed> $data
     */
    public function withMany(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->with($key, $value);
        }
        return $this;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    /**
     * Get the redirect target URL.
     */
    public function getTargetUrl(): string
    {
        return $this->getHeader('Location') ?? '/';
    }

    /**
     * Check whether this is a permanent redirect.
     */
    public function isPermanent(): bool
    {
        return in_array($this->getStatusCode(), [301, 308], true);
    }
}
