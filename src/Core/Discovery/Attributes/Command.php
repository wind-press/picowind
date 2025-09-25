<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class Command
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description = null,
        /** @var array<string> */
        public readonly array $aliases = []
    ) {
    }
}