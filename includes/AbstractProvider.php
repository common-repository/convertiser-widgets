<?php
/**
 * Base hook provider.
 */
namespace Convertiser\Widgets;

/**
 * Base hook provider class.
 *
 * @package Structure
 * @since   1.0.0
 */
abstract class AbstractProvider
{
    /**
     * Plugin instance.
     *
     * @since 1.0.0
     * @var Plugin
     */
    protected $plugin;

    /**
     * Set a reference to the main plugin instance.
     *
     * @param Plugin|AbstractPlugin $plugin Main plugin instance.
     *
     * @return AbstractProvider instance
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;

        return $this;
    }

    /**
     * Register hooks.
     */
    abstract public function registerHooks();
}
