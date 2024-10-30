<?php
/**
 * Admin menu provider
 */
namespace Convertiser\Widgets\Provider;

use Convertiser\Widgets\AbstractProvider;

class Menu extends AbstractProvider
{

    /**
     * Hook in tabs.
     */
    public function registerHooks()
    {
        add_action('admin_menu', array($this, 'settingsMenu'));
    }


    /**
     * Add menu item
     */
    public function settingsMenu()
    {
        add_submenu_page(
            'options-general.php',
            __('Settings &#8212; Convertiser Widgets', 'convertiser-widgets'),
            __('Convertiser Widgets', 'convertiser-widgets'),
            'manage_options',
            'convertiser-widgets-settings',
            array($this, 'settingsPage')
        );
    }


    /**
     * Init the settings page
     */
    public function settingsPage()
    {
        Settings::output();
    }
}
