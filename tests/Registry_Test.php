<?php
/**
 * Proves the registry announces itself on the mmwp_kit_registry_ready hook.
 *
 * @package MammothWP\WpKit
 * @since   2.0.0
 */

namespace MammothWP\WpKit\Tests;

use MammothWP\WpKit\Registry;
use PHPUnit\Framework\TestCase;

final class Registry_Test extends TestCase
{
    /**
     * Reset the fake hook table.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['mmwp_test_hooks'] = [];
    }

    /**
     * Registry::ready() fires the action once with the registry itself.
     *
     * @return void
     */
    public function test_ready_fires_action_with_registry()
    {
        $calls = [];
        add_action('mmwp_kit_registry_ready', function ($registry) use (&$calls) {
            $calls[] = $registry;
        });

        $registry = new Registry();
        $this->assertSame($registry, $registry->ready());
        $this->assertSame([$registry], $calls);
    }
}
