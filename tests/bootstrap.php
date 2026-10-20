<?php
/**
 * PHPUnit bootstrap: fake WordPress hooks, then boot the kit.
 *
 * PHPUnit loads vendor/autoload.php before this file, when ABSPATH is not
 * yet defined, so the kit's Composer `files` entries ran as no-ops. Load
 * them again explicitly.
 *
 * @package MammothWP\WpKit
 * @since   1.0.0
 */

define('ABSPATH', __DIR__ . '/');

require __DIR__ . '/stubs.php';
require dirname(__DIR__) . '/bootstrap.php';

\MammothWP\WpKit\Kit::boot();
