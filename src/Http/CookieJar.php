<?php

declare(strict_types=1);

namespace Veldora\Framework\Http;

class CookieJar
{
    /**
     * The queued cookies list.
     *
     * @var array<array{name: string, value: string, minutes: int, path: string, domain: ?string, secure: bool, httpOnly: bool, sameSite: string, signed: bool}>
     */
    protected array $queued = [];

    /**
     * Queue a cookie for setting on the response.
     */
    public function queue(
        string $name,
        string $value,
        int $minutes = 0,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax',
        bool $signed = false
    ): void {
        $key = $name . ':' . $path;
        $this->queued[$key] = [
            'name' => $name,
            'value' => $value,
            'minutes' => $minutes,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httpOnly' => $httpOnly,
            'sameSite' => $sameSite,
            'signed' => $signed,
        ];
    }

    /**
     * Queue a signed cookie.
     */
    public function queueSigned(
        string $name,
        string $value,
        int $minutes = 0,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): void {
        $appKey = function_exists('config') ? (string) config('app.key', 'default-key') : 'default-key';
        if ($appKey === 'default-key' && function_exists('env')) {
            $appKey = (string) env('APP_KEY', 'default-key');
        }
        $signature = hash_hmac('sha256', $value, $appKey);
        $signedValue = $value . '.' . $signature;

        $this->queue($name, $signedValue, $minutes, $path, $domain, $secure, $httpOnly, $sameSite, true);
    }

    /**
     * Get the queued cookies without clearing.
     *
     * @return array<array{name: string, value: string, minutes: int, path: string, domain: ?string, secure: bool, httpOnly: bool, sameSite: string, signed: bool}>
     */
    public function getQueuedCookies(): array
    {
        return array_values($this->queued);
    }

    /**
     * Queue a cookie deletion.
     */
    public function queueForget(string $name, string $path = '/', ?string $domain = null): void
    {
        $this->queue($name, '', -2628000, $path, $domain);
    }

    /**
     * Get and clear the queued cookies.
     *
     * @return array<array{name: string, value: string, minutes: int, path: string, domain: ?string, secure: bool, httpOnly: bool, sameSite: string, signed: bool}>
     */
    public function flushQueuedCookies(): array
    {
        $queued = array_values($this->queued);
        $this->queued = [];
        return $queued;
    }
}
