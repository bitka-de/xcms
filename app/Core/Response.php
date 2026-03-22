<?php

namespace App\Core;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private ?string $body = null;
    private bool $sent = false;

    public function __construct()
    {
        $this->setDefaultHeaders();
    }

    private function setDefaultHeaders(): void
    {
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
    }

    public function setStatus(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setBody(string $content): self
    {
        $this->body = $content;
        return $this;
    }

    public function html(string $content, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->body = $content;
        return $this;
    }

    public function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public function json(array $data, int $status = 200): self
    {
        $this->statusCode = $status;
        $this->headers['Content-Type'] = 'application/json; charset=utf-8';
        $this->body = json_encode($data);
        return $this;
    }

    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        if ($this->body !== null) {
            echo $this->body;
        }

        $this->sent = true;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getStatus(): int
    {
        return $this->statusCode;
    }
}
