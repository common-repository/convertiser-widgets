<?php
namespace Convertiser\Widgets;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * BEGIN UPGRADE.
 */

/**
 * Get current version of plugin.
 */
$current_version = get_option('convertiser_widgets_version', getPlugin()->getVersion());

/**
 * Now that any upgrade functions are performed, update version in database.
 *
 * This should be the last action on this page.
 *
 * DO NOT PLACE ANY CODE AFTER THIS LINE!
 */
update_option('convertiser_widgets_version', getPlugin()->getVersion());

/**
 * END UPGRADE.
 */
