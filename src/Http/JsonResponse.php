<?php

declare(strict_types=1);

namespace Veldora\Framework\Http;

/**
 * JsonResponse — an HTTP response that automatically encodes data as JSON.
 *
 * Extends the base Response so it can be used anywhere a Response is expected.
 *
 * Usage:
 *   return new JsonResponse(['status' => 'ok', 'user' => $user]);
 *   return JsonResponse::success(['token' => $token]);
 *   return JsonResponse::error('Not found', 404);
 *   return JsonResponse::paginate($items, $meta);
 *
 * @phpstan-consistent-constructor
 */
class JsonResponse extends Response
{
    /**
     * Default JSON encoding flags.
     */
    protected const DEFAULT_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * Create a new JsonResponse.
     *
     * @param array<mixed>|object $data       Data to JSON-encode.
     * @param int                 $statusCode HTTP status code.
     * @param array<string,string> $headers   Extra headers.
     * @param int                 $flags      json_encode() flags.
     */
    public function __construct(
        protected array|object $data = [],
        int $statusCode = 200,
        array $headers = [],
        protected int $flags = self::DEFAULT_FLAGS
    ) {
        $headers['Content-Type'] = 'application/json';
        parent::__construct('', $statusCode, $headers);
        $this->encodeData();
    }

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    /**
     * Return a 200 success envelope.
     *
     * @param array<mixed> $data
     * @param array<string,string> $headers
     */
    public static function success(
        array $data = [],
        string $message = 'OK',
        int $statusCode = 200,
        array $headers = []
    ): static {
        return new static([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode, $headers);
    }

    /**
     * Return an error envelope.
     *
     * @param array<mixed>|null $errors
     * @param array<string,string> $headers
     */
    public static function error(
        string $message,
        int $statusCode = 400,
        ?array $errors = null,
        array $headers = []
    ): static {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return new static($body, $statusCode, $headers);
    }

    /**
     * Return a paginated collection envelope.
     *
     * @param array<mixed>         $items
     * @param array<string, mixed> $meta   e.g. ['current_page'=>1,'total'=>100,...]
     * @param array<string,string> $headers
     */
    public static function paginate(
        array $items,
        array $meta = [],
        int $statusCode = 200,
        array $headers = []
    ): static {
        return new static([
            'success' => true,
            'data'    => $items,
            'meta'    => $meta,
        ], $statusCode, $headers);
    }

    // -------------------------------------------------------------------------
    // Mutation helpers
    // -------------------------------------------------------------------------

    /**
     * Replace the response data and re-encode.
     *
     * @param array<mixed>|object $data
     */
    public function setData(array|object $data): static
    {
        $this->data = $data;
        $this->encodeData();
        return $this;
    }

    /**
     * Retrieve the raw (pre-encoded) data.
     *
     * @return array<mixed>|object
     */
    public function getData(): array|object
    {
        return $this->data;
    }

    /**
     * Merge additional keys into the top-level data array.
     *
     * @param array<mixed> $extra
     */
    public function merge(array $extra): static
    {
        if (is_array($this->data)) {
            $this->data = array_merge($this->data, $extra);
        }
        $this->encodeData();
        return $this;
    }

    /**
     * Change the JSON encoding flags and re-encode.
     */
    public function withFlags(int $flags): static
    {
        $this->flags = $flags;
        $this->encodeData();
        return $this;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * Encode the current data into the response content string.
     */
    protected function encodeData(): void
    {
        $encoded = json_encode($this->data, $this->flags);
        if ($encoded === false) {
            throw new \InvalidArgumentException(
                'JsonResponse: failed to encode data — ' . json_last_error_msg()
            );
        }
        $this->content = $encoded;
    }
}
