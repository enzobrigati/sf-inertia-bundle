<?php

declare(strict_types=1);

namespace EnzoBrigati\InertiaBundle\Modal;

class ModalVisit
{
    /**
     * @param array<string, mixed>|null  $data
     * @param array<string, string>|null $headers
     */
    public function __construct(
        protected ?string $method = null,
        protected ?bool $navigate = null,
        protected ?array $data = null,
        protected ?array $headers = null,
        protected ?ModalConfig $config = null,
        protected ?QueryStringArrayFormat $queryStringArrayFormat = null,
    ) {
    }

    public static function make(): self
    {
        return new self();
    }

    public static function new(): self
    {
        return new self();
    }

    public function method(?string $method): self
    {
        $this->method = $method;

        return $this;
    }

    public function navigate(?bool $navigate = true): self
    {
        $this->navigate = $navigate;

        return $this;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function data(?array $data): self
    {
        $this->data = empty($data) ? null : $data;

        return $this;
    }

    /**
     * @param array<string, string>|null $headers
     */
    public function headers(?array $headers): self
    {
        $this->headers = empty($headers) ? null : $headers;

        return $this;
    }

    public function config(ModalConfig|callable|null $config): self
    {
        if (is_callable($config)) {
            $modalConfig = ModalConfig::new();
            $config($modalConfig);
            $config = $modalConfig;
        }

        $this->config = $config;

        return $this;
    }

    public function queryStringArrayFormat(?QueryStringArrayFormat $queryStringArrayFormat): self
    {
        $this->queryStringArrayFormat = $queryStringArrayFormat;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'navigate' => $this->navigate,
            'data' => $this->data,
            'headers' => $this->headers,
            'config' => $this->config?->toArray(),
            'queryStringArrayFormat' => $this->queryStringArrayFormat?->value,
        ];
    }
}
