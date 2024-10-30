<?php
/**
 * Main Plugin Class
 */
namespace Convertiser\Widgets;

use Convertiser\Widgets\Provider\Notices;
use Convertiser\Widgets\Provider\Settings;

class Plugin extends AbstractPlugin
{

    /**
     * Load plugin.
     */
    public function loadPlugin()
    {
        include_once __DIR__ . '/upgrade.php';

        // Select2 compatibility mode for wp-shortcake
        if (!defined('SELECT2_NOCONFLICT')) {
            define('SELECT2_NOCONFLICT', true);
        }

        do_action('convertiser_widgets_loaded');

        // Check to see if Shortcake is running, with an admin notice if not.
        add_action('admin_init', array($this, 'checkShortcakePresence'));
        add_action('admin_init', array($this, 'checkWordPressVersion'));
        add_action('admin_init', array($this, 'checkPluginConfiguration'));
    }

    /**
     * If Shortcake isn't active, then add an administration notice.
     *
     * This check is optional. The addition of the shortcode UI is via an action hook that is only called in Shortcake.
     * So if Shortcake isn't active, you won't be presented with errors.
     *
     * Here, we choose to tell users that Shortcake isn't active, but equally you could let it be silent.
     *
     * Why not just self-deactivate this plugin? Because then the shortcodes would not be registered either.
     */
    public function checkShortcakePresence()
    {
        if (! defined('SHORTCODE_UI_VERSION') || version_compare(SHORTCODE_UI_VERSION, '0.7.1', '<')) {
            if (isset($_GET['page']) && 'convertiser-widgets-settings' === $_GET['page']) {
                Notices::addNotice('fixed_shortcake_ui');
            }
        }
    }

    /**
     * WP Admin Notice: Checks minimal supported Wordpress version
     */
    public function checkWordPressVersion()
    {
        $basename = $this->getBasename();
        $version  = get_bloginfo('version');
        if (version_compare($version, '4.5', '<') && is_plugin_active($basename)) {
            deactivate_plugins($basename);

            Notices::addNotice('wordpress_version');

            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }

    /**
     * WP Admin Notice: Checks whether plugin is fully configured
     */
    public function checkPluginConfiguration()
    {
        if (isset($_GET['page']) && 'convertiser-widgets-settings' === $_GET['page'] && !$this->isConfigured()) {
            Notices::addNotice('plugin_setup');
        }
    }


    /**
     * Checks whether user provided Website GUID
     * @return bool
     */
    public function isConfigured()
    {
        $guid  = Settings::getOption('website_guid', '');
        $token = Settings::getOption('token', '');

        return ! empty($guid) && ! empty($token);
    }
}
