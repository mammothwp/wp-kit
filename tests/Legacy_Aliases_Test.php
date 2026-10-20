<?php
/**
 * Proves the Gnnpls\WP\* class shims resolve to the new classes.
 *
 * @package MammothWP\WpKit
 * @since   1.0.0
 */

namespace MammothWP\WpKit\Tests;

use MammothWP\WpKit\Compat\Legacy_Aliases;
use PHPUnit\Framework\TestCase;

final class Legacy_Aliases_Test extends TestCase
{
    /**
     * Every legacy class name must alias to its new counterpart.
     *
     * @return array<string,array{0:string}>
     */
    public function classes()
    {
        $cases = [];
        foreach (Legacy_Aliases::CLASSES as $relative) {
            $cases[$relative] = [$relative];
        }
        return $cases;
    }

    /**
     * @dataProvider classes
     *
     * @param string $relative Class name relative to the namespace root.
     *
     * @return void
     */
    public function test_old_name_aliases_new_class($relative)
    {
        $GLOBALS['mmwp_test_deprecated'] = [];
        $old = 'Gnnpls\\WP\\' . $relative;
        $new = 'MammothWP\\WpKit\\' . $relative;

        $this->assertTrue(class_exists($old), $old);
        $this->assertSame((new \ReflectionClass($new))->getName(), (new \ReflectionClass($old))->getName());
        $this->assertContains([$old, $new], $GLOBALS['mmwp_test_deprecated']);
    }

    /**
     * The notice fires once per class, not on every reference.
     *
     * @return void
     */
    public function test_notice_is_emitted_once()
    {
        class_exists('Gnnpls\\WP\\Context');
        $GLOBALS['mmwp_test_deprecated'] = [];
        class_exists('Gnnpls\\WP\\Context');
        $this->assertSame([], $GLOBALS['mmwp_test_deprecated']);
    }

    /**
     * Unrelated and unknown names are left alone.
     *
     * @return void
     */
    public function test_unknown_names_are_ignored()
    {
        $this->assertNull(Legacy_Aliases::new_name('Gnnpls\\WP\\Nope'));
        $this->assertNull(Legacy_Aliases::new_name('Other\\Field'));
        $this->assertFalse(class_exists('Gnnpls\\WP\\Nope'));
    }
}
