<?php

declare(strict_types=1);

namespace Picowind\Core\Discovery;

use Picowind\Core\Container\Container;
use Picowind\Core\Discovery\Attributes\Hook;

final class HookDiscovery implements Discovery
{
    use IsDiscovery;

    /** @var array<array<string, mixed>> */
    private array $hooks = [];

    public function __construct(
        private Container $container,
    ) {
        $this->discoveryItems = new DiscoveryItems();
    }

    /**
     * @param ClassReflector $classReflector
     */
    public function discover(DiscoveryLocation $discoveryLocation, ClassReflector $classReflector): void
    {
        foreach ($classReflector->getPublicMethods() as $methodReflector) {
            $hookAttribute = $methodReflector->getAttribute(Hook::class);

            if (null === $hookAttribute) {
                continue;
            }

            $this->discoveryItems->add($discoveryLocation, [
                'className' => $classReflector->getName(),
                'methodName' => $methodReflector->getName(),
                'hook' => $hookAttribute->name,
                'type' => $hookAttribute->type ?? 'filter',
                'priority' => $hookAttribute->priority ?? 10,
                'acceptedArgs' => $hookAttribute->accepted_args ?? 1,
            ]);
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $discoveryItem) {
            if (
                is_array($discoveryItem)
                && isset($discoveryItem['className'], $discoveryItem['methodName'], $discoveryItem['hook'], $discoveryItem['priority'], $discoveryItem['acceptedArgs'])
            ) {
                $this->hooks[] = [
                    'className' => $discoveryItem['className'],
                    'methodName' => $discoveryItem['methodName'],
                    'hook' => $discoveryItem['hook'],
                    'type' => $discoveryItem['type'] ?? 'filter',
                    'priority' => $discoveryItem['priority'],
                    'acceptedArgs' => $discoveryItem['acceptedArgs'],
                ];
            }
        }
    }

    public function registerHooks(): void
    {
        $groupedHooks = [];

        foreach ($this->hooks as $hook) {
            $priority = $hook['priority'];
            $groupedHooks[$priority][] = $hook;
        }

        ksort($groupedHooks);

        foreach ($groupedHooks as $groupedHook) {
            foreach ($groupedHook as $hook) {
                $this->registerHookFromData($hook);
            }
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function registerHookFromData(array $data): void
    {
        assert(is_string($data['className']));
        assert(is_string($data['methodName']));
        assert(is_string($data['hook']));
        assert(is_int($data['priority']));
        assert(is_int($data['acceptedArgs']));

        $className = $data['className'];
        $methodName = $data['methodName'];
        $hookName = $data['hook'];
        $type = $data['type'] ?? 'filter';
        $priority = $data['priority'];
        $acceptedArgs = $data['acceptedArgs'];

        if (! $this->container->has($className)) {
            return;
        }

        $instance = $this->container->get($className);
        assert(is_object($instance));

        $callback = [$instance, $methodName];
        assert(is_callable($callback));

        if ('action' === $type) {
            add_action($hookName, $callback, $priority, $acceptedArgs);
        } else {
            add_filter($hookName, $callback, $priority, $acceptedArgs);
        }
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function getHooks(): array
    {
        return $this->hooks;
    }
}
