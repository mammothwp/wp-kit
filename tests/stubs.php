<?php
/**
 * Minimal in-memory WordPress hook API for isolated tests.
 *
 * @package MammothWP\WpKit
 * @since   1.0.0
 */

$GLOBALS['mmwp_test_hooks']       = [];
$GLOBALS['mmwp_test_deprecated']  = [];

if (!function_exists('add_filter')) {
    /**
     * Register a callback.
     *
     * @param string   $hook     Hook name.
     * @param callable $callback Callback.
     *
     * @return true
     */
    function add_filter($hook, $callback)
    {
        $GLOBALS['mmwp_test_hooks'][$hook][] = $callback;
        return true;
    }

    /**
     * Register an action callback.
     *
     * @param string   $hook     Hook name.
     * @param callable $callback Callback.
     *
     * @return true
     */
    function add_action($hook, $callback)
    {
        return add_filter($hook, $callback);
    }

    /**
     * Whether a hook has callbacks.
     *
     * @param string $hook Hook name.
     *
     * @return bool
     */
    function has_filter($hook)
    {
        return !empty($GLOBALS['mmwp_test_hooks'][$hook]);
    }

    /**
     * Apply filters.
     *
     * @param string $hook  Hook name.
     * @param mixed  $value Value.
     *
     * @return mixed
     */
    function apply_filters($hook, $value)
    {
        $args = array_slice(func_get_args(), 2);
        foreach (isset($GLOBALS['mmwp_test_hooks'][$hook]) ? $GLOBALS['mmwp_test_hooks'][$hook] : [] as $callback) {
            $value = call_user_func_array($callback, array_merge([$value], $args));
        }
        return $value;
    }

    /**
     * Fire an action.
     *
     * @param string $hook Hook name.
     *
     * @return void
     */
    function do_action($hook)
    {
        $args = array_slice(func_get_args(), 1);
        foreach (isset($GLOBALS['mmwp_test_hooks'][$hook]) ? $GLOBALS['mmwp_test_hooks'][$hook] : [] as $callback) {
            call_user_func_array($callback, $args);
        }
    }

    /**
     * Mirror of core: notice and fire only when the old hook has listeners.
     *
     * @param string $hook        Old hook.
     * @param array  $args        Arguments.
     * @param string $version     Version.
     * @param string $replacement Replacement.
     *
     * @return void
     */
    function do_action_deprecated($hook, $args, $version, $replacement = '')
    {
        if (!has_filter($hook)) {
            return;
        }
        $GLOBALS['mmwp_test_deprecated'][] = [$hook, $replacement];
        call_user_func_array('do_action', array_merge([$hook], $args));
    }

    /**
     * Mirror of core for filters.
     *
     * @param string $hook        Old hook.
     * @param array  $args        Arguments.
     * @param string $version     Version.
     * @param string $replacement Replacement.
     *
     * @return mixed
     */
    function apply_filters_deprecated($hook, $args, $version, $replacement = '')
    {
        if (!has_filter($hook)) {
            return $args[0];
        }
        $GLOBALS['mmwp_test_deprecated'][] = [$hook, $replacement];
        return call_user_func_array('apply_filters', array_merge([$hook], $args));
    }

    /**
     * Record a class deprecation.
     *
     * @param string $class       Old class.
     * @param string $version     Version.
     * @param string $replacement Replacement.
     *
     * @return void
     */
    function _deprecated_class($class, $version, $replacement = '')
    {
        $GLOBALS['mmwp_test_deprecated'][] = [$class, $replacement];
    }

    /**
     * Capability stub.
     *
     * @return bool
     */
    function did_action()
    {
        return false;
    }
}
