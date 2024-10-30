<?php
/**
 * Internationalization provider.
 */
namespace Convertiser\Widgets\Provider;

use Convertiser\Widgets\AbstractProvider;

class Setup extends AbstractProvider
{
    /**
     * Register hooks.
     */
    public function registerHooks()
    {
        register_activation_hook($this->plugin->getFile(), array($this, 'activatePlugin'));
        register_deactivation_hook($this->plugin->getFile(), array($this, 'deactivatePlugin'));

        add_action('admin_init', array($this, 'checkPluginVersion'), 5);
        add_action('admin_init', array($this, 'updatePlugin'));
        add_action('in_plugin_update_message-convertiser-widgets', array($this, 'inPluginUpdateMessage'));
        add_filter('plugin_action_links_' . $this->plugin->getBasename(), array($this, 'pluginActionLinks'));
        add_filter('plugin_row_meta', array($this, 'pluginRowMeta'), 10, 2);
    }

    /**
     * Checks plugin version
     */
    public function checkPluginVersion()
    {
        if (! defined('IFRAME_REQUEST')
            && (get_option('convertiser_widgets_version') !== $this->plugin->getVersion())) {
            $this->activatePlugin();
            do_action('convertiser_widgets_updated');
        }
    }

    /**
     * Check minimal WordPress version and dependencies
     */
    public function checkWordPressVersion()
    {
        $version = get_bloginfo('version');
        if (version_compare($version, '4.5', '<')) {
            deactivate_plugins($this->plugin->getBasename());
            wp_die(__('<strong>Convertiser Widgets</strong> plugin could not be activated because it requires WordPress (v4.5+) to be installed.', 'convertiser-widgets'));
        }
    }

    /**
     * Plugin update action
     */
    public function updatePlugin()
    {
        if (! empty($_GET['do_update_convertiser_widgets'])) {
            $this->updatePluginVersion();

            // Update complete
            Notices::removeNotice('update');
            exit;
        }
    }

    /**
     * Default options
     *
     * Sets up the default options used on the settings page
     */
    public function createDefaultOptions()
    {
        $defaults = array();
        $options  = Settings::getAvailableOptions();

        foreach ($options as $value) {
            if (isset($value['id'], $value['default']) && ! in_array($value['type'], array(
                    'title',
                    'sectionend'
                ), true)
            ) {
                $defaults[$value['id']] = $value['default'];
            }
        }

        add_option(Settings::$prefix, $defaults);
    }

    /**
     * Install Convertiser Plugin
     */
    public function activatePlugin()
    {
        $this->checkWordPressVersion();
        $this->createDefaultOptions();

        // Reset all exiting notices
        Notices::removeAllNotices();

        $this->updatePluginVersion();

        // Trigger action
        do_action('convertiser_widgets_installed');
    }

    /**
     * Deactivate Plugin
     */
    public static function deactivatePlugin()
    {
        return false;
    }

    /**
     * Update plugin version to current
     */
    public function updatePluginVersion()
    {
        delete_option('convertiser_widgets_version');
        add_option('convertiser_widgets_version', $this->plugin->getVersion());
    }


    /**
     * Show plugin changes. Code adapted from W3 Total Cache.
     *
     * @param array $args
     */
    public function inPluginUpdateMessage($args)
    {
        $transient_name = 'convertiser_widgets_upgrade_notice_' . $args['Version'];

        if (false === ($upgrade_notice = get_transient($transient_name))) {
            # TODO: Add real URL here.
            $response = wp_safe_remote_get('https://plugins.svn.wordpress.org/convertiser-widgets/trunk/readme.txt');

            if (! is_wp_error($response) && ! empty($response['body'])) {
                $upgrade_notice = $this->parseUpdateNotice($response['body']);
                set_transient($transient_name, $upgrade_notice, DAY_IN_SECONDS);
            }
        }

        echo wp_kses_post($upgrade_notice);
    }

    /**
     * Parse update notice from readme file
     *
     * @param  string $content
     *
     * @return string
     */
    private function parseUpdateNotice($content)
    {
        // Output Upgrade Notice
        $plugin_version = $this->plugin->getVersion();
        $matches        = null;
        $regexp         = '~==\s*Upgrade Notice\s*==\s*=\s*(.*)\s*=(.*)(=\s*'
                          . preg_quote($plugin_version, '/') . '\s*=|$)~Uis';
        $upgrade_notice = '';

        if (preg_match($regexp, $content, $matches)) {
            $version = trim($matches[1]);
            $notices = (array)preg_split('~[\r\n]+~', trim($matches[2]));

            if (version_compare($plugin_version, $version, '<')) {
                $upgrade_notice .= '<div class="cvr-widgets"><div class="cr--alert cr--alert-danger">';

                foreach ($notices as $index => $line) {
                    $upgrade_notice .= wp_kses_post(
                        preg_replace(
                            '~\[([^\]]*)\]\(([^\)]*)\)~',
                            '<a class="cr--alert-link" href="${2}">${1}</a>',
                            $line
                        )
                    );
                }

                $upgrade_notice .= '</div></div>';
            }
        }

        return wp_kses_post($upgrade_notice);
    }

    /**
     * Show action links on the plugin screen.
     *
     * @param    mixed $links Plugin Action links
     *
     * @return    array
     */
    public function pluginActionLinks($links)
    {
        $action_links = array(
            'settings' => sprintf(
                '<a href="%s" title="%s">%s</a>',
                admin_url('options-general.php?page=convertiser-widgets-settings'),
                esc_attr(__('View Plugin Settings', 'convertiser-widgets')),
                esc_attr(__('Settings', 'convertiser-widgets'))
            ),
        );

        return array_merge($action_links, $links);
    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param    mixed $links Plugin Row Meta
     * @param    mixed $file Plugin Base file
     *
     * @return    array
     */
    public function pluginRowMeta($links, $file)
    {
        if ($file === $this->plugin->getBasename()) {
            $row_meta = array(
                'docs' => sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    esc_url(apply_filters('convertiser_widgets_docs_url', 'https://convertiser.com/')),
                    esc_attr(__('View Plugin Documentation', 'convertiser-widgets')),
                    esc_attr(__('Docs', 'convertiser-widgets'))
                ),

                'support' => sprintf(
                    '<a href="%s" title="%s">%s</a>',
                    esc_url(apply_filters('convertiser_widgets_support_url', 'https://convertiser.com/')),
                    esc_attr(__('Plugin Support', 'convertiser-widgets')),
                    esc_attr(__('Support', 'convertiser-widgets'))
                ),
            );

            return array_merge($links, $row_meta);
        }

        return (array)$links;
    }
}
