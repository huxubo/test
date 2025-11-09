<?php
declare(strict_types=1);

namespace Core;

/**
 * 配置管理单例
 */
class Config
{
    private static ?Config $instance = null;

    /** @var array<string,mixed> */
    private array $items = [];

    private function __construct(array $config)
    {
        $this->items = $config;
    }

    public static function init(array $config): void
    {
        self::$instance = new self($config);
    }

    public static function getInstance(): Config
    {
        if (!self::$instance) {
            throw new \RuntimeException('Config 尚未初始化');
        }

        return self::$instance;
    }

    public function get(?string $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->items;
        }

        $segments = explode('.', $key);
        $value = $this->items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
