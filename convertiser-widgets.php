<?php
/**
 * Plugin Name: Convertiser Widgets
 * Plugin URI: https://convertiser.com/
 * Description: Greatly simplifies Convertiser Widgets usage at WordPress sites.
 * Version: 1.3.1
 * Author: Convertiser
 * Author URI: https://convertiser.com
 * Requires at least: 4.5
 * Tested up to: 5.6
 *
 * Text Domain: convertiser-widgets
 * Domain Path: /languages
 */
namespace Convertiser\Widgets;

use Convertiser\Widgets\Provider\Admin;
use Convertiser\Widgets\Provider\Help;
use Convertiser\Widgets\Provider\I18n;
use Convertiser\Widgets\Provider\Menu;
use Convertiser\Widgets\Provider\Notices;
use Convertiser\Widgets\Provider\Settings;
use Convertiser\Widgets\Provider\Setup;
use Convertiser\Widgets\Provider\Verification;
use Convertiser\Widgets\Provider\Widgets\ComparisonWidget;
use Convertiser\Widgets\Provider\Widgets\ConvertextWidget;
use Convertiser\Widgets\Provider\Widgets\PaydayOffersWidget;
use Convertiser\Widgets\Provider\Widgets\RecommendationsWidget;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';

$version = '1.3.1';

/**
 * Returns the main plugin instance to prevent the need to use globals.
 *
 * @return Plugin
 */
function getPlugin()
{
    static $instance;

    if (null === $instance) {
        $instance = new Plugin();
    }

    return $instance;
}

// Set up the main plugin instance.
getPlugin()->setBasename(plugin_basename(__FILE__))
           ->setDirectory(plugin_dir_path(__FILE__))
           ->setFile(__FILE__)
           ->setSlug('convertiser-widgets')
           ->setVersion($version)
           ->setUrl(plugin_dir_url(__FILE__));

// ~ Before init action
do_action('convertiser_widgets_before_init');

// Register hook providers.
getPlugin()->registerHooks(new Settings())
           ->registerHooks(new I18n())
           ->registerHooks(new Menu())
           ->registerHooks(new Help())
           ->registerHooks(new Notices())
           ->registerHooks(new Setup())
           ->registerHooks(new Admin())
           ->registerHooks(new Verification())
           ->registerHooks(new RecommendationsWidget())
           ->registerHooks(new ComparisonWidget())
           ->registerHooks(new ConvertextWidget())
           ->registerHooks(new PaydayOffersWidget())
;

// ~ After init action
do_action('convertiser_widgets_after_init');

// Load the plugin.
add_action('plugins_loaded', array(getPlugin(), 'loadPlugin'));

// Enable shortcode rendering in widgets
add_filter('widget_text', 'do_shortcode');
