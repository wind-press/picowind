<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

final class ClassReflector
{
    private readonly ReflectionClass $reflectionClass;

    /**
     * @param class-string $className
     */
    public function __construct(string $className)
    {
        $this->reflectionClass = new ReflectionClass($className);
    }

    public function getName(): string
    {
        return $this->reflectionClass->getName();
    }

    /**
     * @template T
     * @param class-string<T> $attributeClass
     * @return T|null
     */
    public function getAttribute(string $attributeClass): object|null
    {
        $attributes = $this->reflectionClass->getAttributes($attributeClass);

        if (empty($attributes)) {
            return null;
        }

        /** @var ReflectionAttribute<T> $attribute */
        $attribute = $attributes[0];
        return $attribute->newInstance();
    }

    /**
     * @return array<MethodReflector>
     */
    public function getPublicMethods(): array
    {
        $methods = [];
        $reflectionMethods = $this->reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($reflectionMethods as $reflectionMethod) {
            // Skip magic methods and inherited methods from base classes
            if (str_starts_with((string) $reflectionMethod->getName(), '__') && $reflectionMethod->getName() !== '__invoke') {
                continue;
            }

            if ($reflectionMethod->getDeclaringClass()->getName() !== $this->reflectionClass->getName()) {
                continue;
            }

            $methods[] = new MethodReflector($reflectionMethod);
        }

        return $methods;
    }
}