<?php
/**
 * Internationalization provider.
 */
namespace Convertiser\Widgets\Provider;

use Convertiser\Widgets\AbstractProvider;

/**
 * Internationalization provider class.
 */
class I18n extends AbstractProvider
{
    /**
     * Register hooks.
     *
     * Loads the text domain during the `plugins_loaded` action.
     */
    public function registerHooks()
    {
        if (did_action('plugins_loaded')) {
            $this->loadTextdomain();
        } else {
            add_action('plugins_loaded', array($this, 'loadTextdomain'));
        }
    }

    /**
     * Load the text domain to localize the plugin.
     */
    public function loadTextdomain()
    {
        $plugin_rel_path = dirname($this->plugin->getBasename()) . '/languages';
        load_plugin_textdomain($this->plugin->getSlug(), false, $plugin_rel_path);
    }
}
