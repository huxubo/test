<?php
declare(strict_types=1);

namespace Models;

/**
 * 基础模型类，负责数组到对象的映射
 */
abstract class BaseModel
{
    /**
     * @param array<string,mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function fill(array $attributes): void
    {
        $ref = new \ReflectionClass($this);
        foreach ($attributes as $key => $value) {
            if (!$ref->hasProperty($key)) {
                continue;
            }

            $property = $ref->getProperty($key);
            $type = $property->getType();
            if ($type instanceof \ReflectionNamedType && $value !== null) {
                switch ($type->getName()) {
                    case 'int':
                        $value = (int)$value;
                        break;
                    case 'float':
                        $value = (float)$value;
                        break;
                    case 'bool':
                        $value = (bool)$value;
                        break;
                    case 'string':
                        $value = (string)$value;
                        break;
                }
            }

            $this->{$key} = $value;
        }
    }

    /**
     * 将数组转换为模型实例
     * @param array<string,mixed> $row
     */
    public static function fromArray(array $row): static
    {
        return new static($row);
    }
}
