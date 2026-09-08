<?php

declare(strict_types=1);

namespace Veldora\Framework\Http;

/**
 * ResponseFactory — a central factory for creating all response types.
 *
 * Bind this in the container as a singleton and inject it into controllers,
 * or use the global response() helper function.
 *
 * Usage:
 *   $factory = new ResponseFactory();
 *
 *   return $factory->json(['user' => $user]);
 *   return $factory->redirect('/dashboard');
 *   return $factory->back();
 *   return $factory->make('Hello World', 200);
 *   return $factory->view('home', ['title' => 'Home']);
 *   return $factory->download('/path/to/file.pdf');
 *   return $factory->noContent();
 */
class ResponseFactory
{
    // -------------------------------------------------------------------------
    // Plain responses
    // -------------------------------------------------------------------------

    /**
     * Create a basic HTTP response with arbitrary content.
     *
     * @param array<string, string> $headers
     */
    public function make(
        mixed $content = '',
        int $statusCode = 200,
        array $headers = []
    ): Response {
        return new Response($content, $statusCode, $headers);
    }

    /**
     * Return a 204 No Content response.
     */
    public function noContent(): Response
    {
        return new Response('', 204);
    }

    /**
     * Return a plain-text response.
     *
     * @param array<string, string> $headers
     */
    public function text(string $content, int $statusCode = 200, array $headers = []): Response
    {
        $headers['Content-Type'] = 'text/plain; charset=UTF-8';
        return new Response($content, $statusCode, $headers);
    }

    /**
     * Return an HTML response.
     *
     * @param array<string, string> $headers
     */
    public function html(string $content, int $statusCode = 200, array $headers = []): Response
    {
        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new Response($content, $statusCode, $headers);
    }

    // -------------------------------------------------------------------------
    // JSON responses
    // -------------------------------------------------------------------------

    /**
     * Create a JSON response.
     *
     * @param array<mixed>|object  $data
     * @param array<string,string> $headers
     */
    public function json(
        array|object $data = [],
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        return new JsonResponse($data, $statusCode, $headers);
    }

    /**
     * Return a success JSON envelope.
     *
     * @param array<mixed>         $data
     * @param array<string,string> $headers
     */
    public function success(
        array $data = [],
        string $message = 'OK',
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        return JsonResponse::success($data, $message, $statusCode, $headers);
    }

    /**
     * Return an error JSON envelope.
     *
     * @param array<mixed>|null    $errors
     * @param array<string,string> $headers
     */
    public function error(
        string $message,
        int $statusCode = 400,
        ?array $errors = null,
        array $headers = []
    ): JsonResponse {
        return JsonResponse::error($message, $statusCode, $errors, $headers);
    }

    /**
     * Return a paginated JSON envelope.
     *
     * @param array<mixed>         $items
     * @param array<string, mixed> $meta
     * @param array<string,string> $headers
     */
    public function paginate(
        array $items,
        array $meta = [],
        int $statusCode = 200,
        array $headers = []
    ): JsonResponse {
        return JsonResponse::paginate($items, $meta, $statusCode, $headers);
    }

    // -------------------------------------------------------------------------
    // Redirect responses
    // -------------------------------------------------------------------------

    /**
     * Create a redirect response to a given URL.
     *
     * @param array<string, string> $headers
     */
    public function redirect(
        string $url,
        int $statusCode = 302,
        array $headers = []
    ): RedirectResponse {
        return new RedirectResponse($url, $statusCode, $headers);
    }

    /**
     * Redirect back to the previous URL (Referer), with an optional fallback.
     */
    public function back(string $fallback = '/'): RedirectResponse
    {
        return RedirectResponse::back($fallback);
    }

    /**
     * Redirect to the stored "intended" URL or a default.
     */
    public function intended(string $default = '/'): RedirectResponse
    {
        return RedirectResponse::intended($default);
    }

    /**
     * Redirect with a permanent 301 status.
     */
    public function permanentRedirect(string $url): RedirectResponse
    {
        return new RedirectResponse($url, 301);
    }

    // -------------------------------------------------------------------------
    // File / download responses
    // -------------------------------------------------------------------------

    /**
     * Stream a file as a download (Content-Disposition: attachment).
     *
     * @param array<string, string> $headers
     */
    public function download(
        string $filePath,
        ?string $filename = null,
        array $headers = []
    ): Response {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $filename ??= basename($filePath);

        $headers['Content-Type']        = mime_content_type($filePath) ?: 'application/octet-stream';
        $headers['Content-Disposition'] = "attachment; filename=\"{$filename}\"";
        $headers['Content-Length']      = (string) filesize($filePath);
        $headers['Cache-Control']       = 'no-cache, must-revalidate';

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        return new Response($content, 200, $headers);
    }

    /**
     * Stream a file inline in the browser (Content-Disposition: inline).
     *
     * @param array<string, string> $headers
     */
    public function file(
        string $filePath,
        ?string $filename = null,
        array $headers = []
    ): Response {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }

        $filename ??= basename($filePath);

        $headers['Content-Type']        = mime_content_type($filePath) ?: 'application/octet-stream';
        $headers['Content-Disposition'] = "inline; filename=\"{$filename}\"";
        $headers['Content-Length']      = (string) filesize($filePath);

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read file: {$filePath}");
        }

        return new Response($content, 200, $headers);
    }

    // -------------------------------------------------------------------------
    // View response
    // -------------------------------------------------------------------------

    /**
     * Return a rendered view as an HTML response.
     *
     * Delegates to the global view() helper when available; otherwise expects
     * a plain PHP view file at $name.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     */
    public function view(
        string $name,
        array $data = [],
        int $statusCode = 200,
        array $headers = []
    ): Response {
        if (function_exists('view')) {
            $content = view($name, $data);
        } else {
            // Basic fallback: require the file directly
            ob_start();
            extract($data, EXTR_SKIP);
            require $name;
            $content = (string) ob_get_clean();
        }

        $headers['Content-Type'] = 'text/html; charset=UTF-8';
        return new Response($content, $statusCode, $headers);
    }
}
