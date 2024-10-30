<?php
/**
 * Display notices in admin.
 */
namespace Convertiser\Widgets\Provider;

use Convertiser\Widgets\AbstractProvider;
use function Convertiser\Widgets\getPlugin;

class Notices extends AbstractProvider
{


    /**
     * Constructor
     */
    public function registerHooks()
    {
        # Decrease priority for `addNotices` so it run *after* notices has been added.
        add_action('admin_init', array($this, 'addNotices'), 11);
        add_action('admin_enqueue_scripts', array($this, 'attachScripts'));

        # register ajax handlers for existing notices
        $notices = get_option(self::key(), array());
        /** @var array $notices */
        foreach ($notices as $notice) {
            add_action('wp_ajax_dismiss_notice_' . $notice, array($this, 'dismissNotice'));
        }
    }

    /**
     * Attach related scripts
     */
    public function attachScripts()
    {
        wp_enqueue_script(
            self::key(),
            $this->plugin->getUrl('/assets/js/notice.min.js'),
            array('jquery'),
            $this->plugin->getVersion(),
            false
        );
    }

    /**
     * Returns option name used to save notice slugs
     * @return string
     */
    public static function key()
    {
        return getPlugin()->getSlug() . '_admin_notices';
    }

    /**
     * Returns option name used to save dismissed notice slugs
     * @return string
     */
    public static function dismissKey()
    {
        return getPlugin()->getSlug() . '_dismissed_admin_notices';
    }

    /**
     * Add notices + styles if needed.
     */
    public function addNotices()
    {
        $notices = get_option(self::key(), array());

        if ($notices) {
            /** @var array $notices */
            foreach ($notices as $notice) {
                if (self::isDismissed($notice)) {
                    continue;
                }

                #  Use handler if defined, otherwise try to include view file
                $handler   = sprintf('show_%s_notice', $notice);
                $view_file = __DIR__ . '/Notices/' . $notice . '.php';

                if (method_exists($this, $handler)) {
                    add_action('admin_notices', array($this, $handler));
                } elseif (file_exists($view_file)) {
                    add_action('admin_notices', function () use ($view_file) {
                        /** @noinspection PhpIncludeInspection */
                        include_once $view_file;
                    });
                }

            }
            # Reset state
            self::removeAllNotices();
        }
    }

    /**
     * Remove all notices
     */
    public static function removeAllNotices()
    {
        delete_option(self::key());
    }

    /**
     * Show a notice
     *
     * @param  string $name
     */
    public static function addNotice($name)
    {
        $notices = array_unique(array_merge(get_option(self::key(), array()), array($name)));
        update_option(self::key(), $notices);
    }

    /**
     * Remove a notice from being displayed
     *
     * @param  string $name
     */
    public static function removeNotice($name)
    {
        $notices = array_diff(get_option(self::key(), array()), array($name));
        update_option(self::key(), $notices);
    }

    /**
     * See if a notice is being shown
     *
     * @param  string $name
     *
     * @return boolean
     */
    public static function hasNotice($name)
    {
        return in_array($name, get_option(self::key(), array()), true);
    }

    /**
     * Dismisses notice using ajax request for user
     */
    public function dismissNotice()
    {
        $name = isset($_POST['notice_id']) ? sanitize_text_field($_POST['notice_id']) : '';
        if (! self::hasNotice($name)) {
            wp_die('Invalid notice: ' . $name);
        }

        $notices = array_unique(array_merge(get_option(self::dismissKey(), array()), array($name)));
        update_option(self::dismissKey(), $notices);

        wp_die();
    }

    /**
     * Checks whether given notice has been dismissed
     *
     * @param string $name
     *
     * @return bool
     */
    public static function isDismissed($name)
    {
        return in_array($name, get_option(self::dismissKey(), array()), true);
    }
}
