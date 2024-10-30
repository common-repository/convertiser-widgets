<?php
/**
 * Admin View: Settings
 */

use Convertiser\Widgets\Provider\Settings;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<div class="wrap cvr-widgets">
    <form
        method="<?php echo esc_attr(apply_filters('convertiser_widgets_settings_form_method_tab_' . $current_tab, 'post')); ?>"
        id="mainform" action="options.php" enctype="multipart/form-data">
        <?php settings_fields(Settings::$prefix); ?>
        <h2 class="nav-tab-wrapper cr--nav-tab-wrapper">
            <?php
            /** @var array $tabs */
            foreach ($tabs as $name => $label) {
                echo '<a href="' . admin_url('options-general.php?page=convertiser-widgets-settings&tab=' . $name)
                     . '" class="nav-tab ' . ($current_tab === $name ? 'nav-tab-active' : '') . '">' . $label . '</a>';
            }

            do_action('convertiser_widgets_settings_tabs');
            ?>
        </h2>

        <?php
        do_action('convertiser_widgets_sections_' . $current_tab);
        do_action('convertiser_widgets_settings_' . $current_tab);
        ?>

        <p class="submit">
            <?php if (! isset($GLOBALS['hide_save_button'])) : ?>
                <input name="save" class="button-primary" type="submit"
                       value="<?php esc_attr_e('Save changes', 'convertiser-widgets'); ?>"/>
            <?php endif; ?>
            <input type="hidden" name="subtab" id="last_tab"/>
            <input type="hidden" name="tab" value="<?php echo $current_tab; ?>"/>
            <input type="hidden" name="section" value="<?php echo $current_section ?>"/>
        </p>
    </form>
</div>
