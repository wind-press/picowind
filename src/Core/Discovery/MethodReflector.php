<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

use ReflectionAttribute;
use ReflectionMethod;

final class MethodReflector
{
    public function __construct(
        private readonly ReflectionMethod $reflectionMethod
    ) {
    }

    public function getName(): string
    {
        return $this->reflectionMethod->getName();
    }

    public function isStatic(): bool
    {
        return $this->reflectionMethod->isStatic();
    }

    /**
     * @template T
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    public function getAttribute(string $attributeClass): ?object
    {
        $attributes = $this->reflectionMethod->getAttributes($attributeClass);

        if (empty($attributes)) {
            return null;
        }

        /** @var ReflectionAttribute<T> $attribute */
        $attribute = $attributes[0];
        return $attribute->newInstance();
    }

    /**
     * @template T
     * @param class-string<T> $attributeClass
     * @return array<T>
     */
    public function getAttributes(string $attributeClass): array
    {
        $attributes = $this->reflectionMethod->getAttributes($attributeClass);

        if (empty($attributes)) {
            return [];
        }

        return array_map(
            fn (ReflectionAttribute $attribute) => $attribute->newInstance(),
            $attributes
        );
    }
}