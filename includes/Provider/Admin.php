<?php
/**
 * Admin UI provider.
 */
namespace Convertiser\Widgets\Provider;

use Convertiser\Widgets\AbstractProvider;

/**
 * Internationalization provider class.
 */
class Admin extends AbstractProvider
{
    /**
     * Register hooks.
     */
    public function registerHooks()
    {
        add_action('admin_enqueue_scripts', array($this, 'adminStyles'));
        add_action('admin_enqueue_scripts', array($this, 'adminScripts'));
    }

    /**
     * Tests if we are inside plugin management area
     * @return bool
     */
    public function isAdminScreen()
    {
        $screen = get_current_screen();
        $ids    = array(
            'settings_page_convertiser-widgets-settings',
        );

        return in_array($screen->id, $ids, true);
    }


    /**
     * Enqueue styles
     */
    public function adminStyles()
    {
        # do not append any styles outside of our own screens
        if (! $this->isAdminScreen()) {
            return;
        }

        // General styles
        wp_enqueue_style(
            'convertiser-widgets/styles',
            $this->plugin->getUrl('/assets/css/general.css'),
            array(),
            $this->plugin->getVersion()
        );
    }

    /**
     * Enqueue scripts
     */
    public function adminScripts()
    {

        # do not append any scripts outside of our own screens
        if (! $this->isAdminScreen()) {
            return;
        }

        // UI Libs
        wp_enqueue_script(
            'convertiser-bootstrap-prefixed',
            $this->plugin->getUrl('/assets/js/bootstrap/bootstrap-prefixed.min.js'),
            array('jquery'),
            $this->plugin->getVersion()
        );

        wp_enqueue_script(
            'convertiser-widgets-general',
            $this->plugin->getUrl('/assets/js/general.min.js'),
            array('jquery'),
            $this->plugin->getVersion(),
            false
        );
    }
}
