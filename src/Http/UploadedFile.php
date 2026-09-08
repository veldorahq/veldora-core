<?php

declare(strict_types=1);

namespace Veldora\Framework\Http;

/**
 * UploadedFile — wraps a single entry from $_FILES with validation helpers.
 *
 * Usage:
 *   $file = UploadedFile::fromGlobal('avatar');
 *
 *   if ($file && $file->isValid()) {
 *       $file->validate(['image/jpeg','image/png'], maxKb: 2048);
 *       $path = $file->store('uploads/avatars');
 *   }
 */
class UploadedFile
{
    /** PHP upload error code → human-readable message map. */
    protected const ERROR_MESSAGES = [
        UPLOAD_ERR_INI_SIZE   => 'The file exceeds the upload_max_filesize directive.',
        UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the MAX_FILE_SIZE HTML form directive.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];

    /**
     * Create a new UploadedFile instance.
     *
     * @param string $originalName  The client-side filename (e.g. "photo.jpg").
     * @param string $mimeType      MIME type reported by the browser.
     * @param string $tmpPath       Temporary file path on the server.
     * @param int    $size          File size in bytes.
     * @param int    $error         PHP upload error code (UPLOAD_ERR_*).
     */
    public function __construct(
        protected string $originalName,
        protected string $mimeType,
        protected string $tmpPath,
        protected int    $size,
        protected int    $error = UPLOAD_ERR_OK
    ) {}

    // -------------------------------------------------------------------------
    // Factory helpers
    // -------------------------------------------------------------------------

    /**
     * Build an UploadedFile from $_FILES[$field].
     * Returns null when the field is absent or no file was sent.
     */
    public static function fromGlobal(string $field): ?static
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $f = $_FILES[$field];

        return new static(
            $f['name'],
            $f['type'],
            $f['tmp_name'],
            (int) $f['size'],
            (int) $f['error']
        );
    }

    /**
     * Build an array of UploadedFile instances from a multi-file field.
     *
     * @return array<static>
     */
    public static function fromGlobalMultiple(string $field): array
    {
        if (!isset($_FILES[$field]) || !is_array($_FILES[$field]['name'])) {
            return [];
        }

        $files  = [];
        $count  = count($_FILES[$field]['name']);

        for ($i = 0; $i < $count; $i++) {
            if ((int) $_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $files[] = new static(
                $_FILES[$field]['name'][$i],
                $_FILES[$field]['type'][$i],
                $_FILES[$field]['tmp_name'][$i],
                (int) $_FILES[$field]['size'][$i],
                (int) $_FILES[$field]['error'][$i]
            );
        }

        return $files;
    }

    // -------------------------------------------------------------------------
    // Status & metadata
    // -------------------------------------------------------------------------

    /** Returns true when the upload succeeded without errors. */
    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpPath);
    }

    /** PHP upload error code. */
    public function getError(): int
    {
        return $this->error;
    }

    /** Human-readable upload error message. */
    public function getErrorMessage(): string
    {
        return self::ERROR_MESSAGES[$this->error] ?? 'Unknown upload error.';
    }

    /** Original filename as provided by the browser. */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    /** File extension from the original filename (lowercase, without dot). */
    public function getExtension(): string
    {
        return strtolower(pathinfo($this->originalName, PATHINFO_EXTENSION));
    }

    /** Browser-reported MIME type. */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Real MIME type detected server-side via finfo (more trustworthy than browser-reported).
     */
    public function getRealMimeType(): string
    {
        if (!$this->isValid()) {
            return $this->mimeType;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return $finfo->file($this->tmpPath) ?: $this->mimeType;
    }

    /** File size in bytes. */
    public function getSize(): int
    {
        return $this->size;
    }

    /** File size in kilobytes. */
    public function getSizeInKb(): float
    {
        return round($this->size / 1024, 2);
    }

    /** Temporary server-side path. */
    public function getTmpPath(): string
    {
        return $this->tmpPath;
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    /**
     * Assert that the file matches allowed MIME types and max size.
     *
     * @param array<string> $allowedMimes  e.g. ['image/jpeg', 'image/png']
     * @param int           $maxKb         Maximum allowed size in kilobytes.
     *
     * @throws \RuntimeException on validation failure.
     */
    public function validate(array $allowedMimes = [], int $maxKb = 0): static
    {
        if (!$this->isValid()) {
            throw new \RuntimeException('Upload error: ' . $this->getErrorMessage());
        }

        if (!empty($allowedMimes)) {
            $real = $this->getRealMimeType();
            if (!in_array($real, $allowedMimes, true)) {
                throw new \RuntimeException(
                    "File type '{$real}' is not allowed. Allowed: " . implode(', ', $allowedMimes)
                );
            }
        }

        if ($maxKb > 0 && $this->getSizeInKb() > $maxKb) {
            throw new \RuntimeException(
                "File size ({$this->getSizeInKb()} KB) exceeds the maximum allowed ({$maxKb} KB)."
            );
        }

        return $this;
    }

    // -------------------------------------------------------------------------
    // Storage
    // -------------------------------------------------------------------------

    /**
     * Move the uploaded file to a destination directory.
     *
     * @param string      $directory  Destination directory path.
     * @param string|null $filename   Optional filename; defaults to a UUID-based name.
     *
     * @return string  The full path of the saved file.
     *
     * @throws \RuntimeException when the move fails.
     */
    public function store(string $directory, ?string $filename = null): string
    {
        if (!$this->isValid()) {
            throw new \RuntimeException('Cannot store an invalid upload: ' . $this->getErrorMessage());
        }

        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new \RuntimeException("Failed to create directory: {$directory}");
            }
        }

        $filename ??= $this->generateUniqueFilename();
        $destination = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($this->tmpPath, $destination)) {
            throw new \RuntimeException("Failed to move uploaded file to: {$destination}");
        }

        return $destination;
    }

    /**
     * Store with the original client filename (sanitised).
     *
     * @throws \RuntimeException when the move fails.
     */
    public function storeAs(string $directory, string $filename): string
    {
        return $this->store($directory, $filename);
    }

    /**
     * Read and return the raw file contents.
     */
    public function getContents(): string
    {
        if (!$this->isValid()) {
            throw new \RuntimeException('Cannot read an invalid upload.');
        }

        $contents = file_get_contents($this->tmpPath);
        if ($contents === false) {
            throw new \RuntimeException("Failed to read uploaded file: {$this->tmpPath}");
        }
        return $contents;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Generate a unique filename preserving the original extension.
     */
    protected function generateUniqueFilename(): string
    {
        $ext = $this->getExtension();
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        return $ext !== '' ? "{$uuid}.{$ext}" : $uuid;
    }
}
