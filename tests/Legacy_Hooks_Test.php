<?php
/**
 * Proves the deprecated mwp_* hooks still reach their listeners.
 *
 * @package MammothWP\WpKit
 * @since   1.0.0
 */

namespace MammothWP\WpKit\Tests;

use MammothWP\WpKit\Compat\Legacy_Hooks;
use MammothWP\WpKit\Registry;
use PHPUnit\Framework\TestCase;

final class Legacy_Hooks_Test extends TestCase
{
    /**
     * Reset the fake hook table.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['mmwp_test_hooks']      = [];
        $GLOBALS['mmwp_test_deprecated'] = [];
    }

    /**
     * Every new hook has an old twin, and all twins are distinct.
     *
     * @return void
     */
    public function test_map_covers_all_six_hooks()
    {
        $this->assertCount(6, Legacy_Hooks::MAP);
        $this->assertCount(6, array_unique(Legacy_Hooks::MAP));
        $this->assertSame('mwp_registry_ready', Legacy_Hooks::old_name('mmwp_kit_registry_ready'));
        $this->assertNull(Legacy_Hooks::old_name('mmwp_kit_unknown'));
    }

    /**
     * An old action listener fires, with a deprecation record, alongside the new one.
     *
     * @return void
     */
    public function test_old_action_still_fires()
    {
        $calls = [];
        add_action('mwp_registry_ready', function ($registry) use (&$calls) {
            $calls[] = $registry;
        });
        add_action('mmwp_kit_registry_ready', function ($registry) use (&$calls) {
            $calls[] = 'new';
        });

        $registry = (new Registry())->ready();

        $this->assertSame(['new', $registry], $calls);
        $this->assertSame([['mwp_registry_ready', 'mmwp_kit_registry_ready']], $GLOBALS['mmwp_test_deprecated']);
    }

    /**
     * An old filter listener can still change the value.
     *
     * @return void
     */
    public function test_old_filter_still_applies()
    {
        add_filter('mwp_operation_result', function ($value) {
            return $value . '+old';
        });

        $this->assertSame('x+old', Legacy_Hooks::filter('mmwp_kit_operation_result', ['x']));
    }

    /**
     * Nobody listening on the old hook means no notice.
     *
     * @return void
     */
    public function test_no_listener_no_notice()
    {
        (new Registry())->ready();
        $this->assertSame('x', Legacy_Hooks::filter('mmwp_kit_operation_result', ['x']));
        $this->assertSame([], $GLOBALS['mmwp_test_deprecated']);
    }
}
