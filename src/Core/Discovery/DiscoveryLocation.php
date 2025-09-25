<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

final class DiscoveryLocation
{
    public function __construct(
        public readonly string $namespace,
        public readonly string $path
    ) {
    }
}