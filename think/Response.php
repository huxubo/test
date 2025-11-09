<?php
declare(strict_types=1);

namespace think;

class Response
{
    public function __construct(
        private string $content,
        private int $status = 200,
        private array $headers = []
    ) {
    }

    public static function make(string $content, int $status = 200, array $headers = []): self
    {
        return new self($content, $status, $headers);
    }

    public function status(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function header(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            $headers = $this->headers;
            $normalized = array_change_key_case($headers, CASE_LOWER);
            if (!array_key_exists('content-type', $normalized)) {
                $headers['Content-Type'] = 'text/html; charset=UTF-8';
            }

            foreach ($headers as $key => $value) {
                header($key . ': ' . $value);
            }
        }

        echo $this->content;
    }
}
