<?php
/**
 * Settings Class.
 */
namespace Convertiser\Widgets\Provider;

use Convertiser\Widgets\AbstractProvider;
use function Convertiser\Widgets\getPlugin;

class Settings extends AbstractProvider
{
    private static $settings = array();
    public static $prefix    = 'convertiser_widgets';


    /**
     * Hooks setup.
     */
    public function registerHooks()
    {
        add_action('admin_init', array( __NAMESPACE__ . '\\Settings', 'registerSetting' ));
    }

    /**
     * Register settings pages
     * @return array|mixed
     */
    public static function getSettingsPages()
    {
        if (empty(self::$settings)) {
            $settings = array();
            $settings[] = new Settings\General();
            $settings[] = new Settings\ConvertextWidget();
            $settings[] = new Settings\ComparisonWidget();
            $settings[] = new Settings\RecommendationsWidget();
            $settings[] = new Settings\PaydayOffersWidget();

            self::$settings = apply_filters('convertiser_widgets_get_settings_pages', $settings);
        }

        return self::$settings;
    }

    /**
     * Setup namespace for settings
     */
    public static function registerSetting()
    {
        register_setting(self::$prefix, self::$prefix, array( __NAMESPACE__ . '\\Settings', 'sanitizeFields' ));
        $settings = self::getSettingsPages();

        $noop = function () {
        };
        /** @var array $settings */
        foreach ($settings as $section) {
            if (! method_exists($section, 'getSettings')) {
                continue;
            }

            add_settings_section($section->getID(), $section->getLabel(), $noop, self::$prefix);
            $subsections = array_unique(array_merge(array( '' ), array_keys($section->getSections())));

            foreach ($subsections as $subsection) {
                foreach ($section->getSettings($subsection) as $value) {
                    if (isset($value['id']) && ! in_array($value['type'], array( 'title', 'sectionend' ), true)) {
                        add_settings_field($value['id'], $value['title'], $noop, self::$prefix, $section->getID());
                    }
                }
            }
        }
    }

    /**
     * Return all registered options as flat array
     *
     * @param string $tab (Tab of interest)
     * @return array
     */
    public static function getAvailableOptions($tab = '')
    {
        $options = array();

        $settings = self::getSettingsPages();
        /** @var array $settings */
        foreach ($settings as $section) {
            if (! method_exists($section, 'getSettings')) {
                continue;
            }

            if ('' !== $tab && $section->getID() !== $tab) {
                continue;
            }

            $subsections = array_unique(array_merge(array( '' ), array_keys($section->getSections())));

            foreach ($subsections as $subsection) {
                foreach ($section->getSettings($subsection) as $value) {
                    $options[] = $value;
                }
            }
        }

        return $options;
    }

    /**
     * Add a message
     *
     * @param string $text
     */
    public static function addMessage($text)
    {
        add_settings_error(self::$prefix, esc_attr('settings_updated'), $text, 'updated');
    }

    /**
     * Add an error
     *
     * @param string $text
     */
    public static function addError($text)
    {
        add_settings_error(self::$prefix, esc_attr('settings_updated'), $text, 'error');
    }

    /**
     * Output messages + errors
     */
    public static function showMessages()
    {
        settings_errors(self::$prefix);
    }

    /**
     * Settings page.
     *
     * Handles the display of the main convertiser settings page in admin.
     */
    public static function output()
    {
        global $current_section, $current_tab;

        do_action('convertiser_widgets_settings_start');

        wp_enqueue_script(
            'convertiser_widgets_settings',
            getPlugin()->getUrl('/assets/js/settings.min.js'),
            array( 'jquery' ),
            getPlugin()->getVersion(),
            true
        );

        wp_localize_script('convertiser_widgets_settings', 'convertiser_widgets_settings_params', array(
            'i18n_nav_warning' => __(
                'The changes you made will be lost if you navigate away from this page.',
                'convertiser-widgets'
            )
        ));

        // Include settings pages
        self::getSettingsPages();

        // Get current tab/section
        $current_tab     = empty($_GET['tab']) ? 'general' : sanitize_title($_GET['tab']);
        $current_section = empty($_REQUEST['section']) ? '' : sanitize_title($_REQUEST['section']);

        // Add any posted messages
        if (! empty($_GET['convertiser_widgets_error'])) {
            self::addError(sanitize_text_field(wp_unslash($_GET['convertiser_widgets_error'])));
        }

        if (! empty($_GET['convertiser_widgets_message'])) {
            self::addMessage(sanitize_text_field(wp_unslash($_GET['convertiser_widgets_message'])));
        }

        self::showMessages();

        // Get tabs for the settings page
        $tabs = apply_filters('convertiser_widgets_settings_tabs_array', array());
        include_once __DIR__ . '/Settings/Views/general.php';
    }

    /**
     * Returns all registered options
     * @return array
     */
    public static function getOptions()
    {
        return get_option(self::$prefix, array());
    }

    /**
     * Get value with Settings API
     * @param string $option_name
     * @param string $default
     * @return mixed
     */
    public static function getOption($option_name, $default = '')
    {

        // Get all options from db
        $options = self::getOptions();

        // Find requested item
        if (isset($options[$option_name])) {
            $option_value = $options[$option_name];
            if (is_array($option_value)) {
                $option_value = json_decode(stripslashes(json_encode($option_value, JSON_UNESCAPED_UNICODE)), true);
            } else {
                $option_value = stripslashes($option_value);
            }
        } else {
            $option_value = $default;
        }

        return $option_value;
    }

    /**
     * Output admin fields.
     *
     * Loops though the plugin options array and outputs each field.
     *
     * @param array $options Opens array to output
     */
    public static function outputFields($options)
    {
        foreach ($options as $value) {
            if (! isset($value['type'])) {
                continue;
            }
            if (! isset($value['id'])) {
                $value['id'] = '';
            }
            if (! isset($value['title'])) {
                $value['title'] = isset($value['name']) ? $value['name'] : '';
            }
            if (! isset($value['class'])) {
                $value['class'] = '';
            }
            if (! isset($value['css'])) {
                $value['css'] = '';
            }
            if (! isset($value['default'])) {
                $value['default'] = '';
            }
            if (! isset($value['desc'])) {
                $value['desc'] = '';
            }
            if (! isset($value['desc_tip'])) {
                $value['desc_tip'] = false;
            }
            if (! isset($value['placeholder'])) {
                $value['placeholder'] = '';
            }

            // Custom attribute handling
            $custom_attributes = array();

            if (! empty($value['custom_attributes']) && is_array($value['custom_attributes'])) {
                foreach ($value['custom_attributes'] as $attribute => $attribute_value) {
                    $custom_attributes[] = esc_attr($attribute) . '="' . esc_attr($attribute_value) . '"';
                }
            }

            // Description handling
            $field_description = self::getFieldDescription($value);
            $tooltip_html = $field_description['tooltip_html'];
            $description = $field_description['description'];

            // Switch based on type
            switch ($value['type']) {
                // Section Titles
                case 'title':
                    if (! empty($value['title'])) {
                        echo '<h3>' . esc_html($value['title']) . '</h3>';
                    }
                    if (! empty($value['desc'])) {
                        echo wpautop(wptexturize(wp_kses_post($value['desc'])));
                    }
                    echo '<table class="form-table">'. "\n\n";
                    if (! empty($value['id'])) {
                        do_action('convertiser_widgets_settings_' . sanitize_title($value['id']));
                    }
                    break;

                // Section Ends
                case 'sectionend':
                    if (! empty($value['id'])) {
                        do_action('convertiser_widgets_settings_' . sanitize_title($value['id']) . '_end');
                    }
                    echo '</table>';
                    if (! empty($value['id'])) {
                        do_action('convertiser_widgets_settings_' . sanitize_title($value['id']) . '_after');
                    }
                    break;

                // Standard text inputs and subtypes like 'number'
                case 'text':
                case 'email':
                case 'number':
                case 'password':
                    $type         = $value['type'];
                    $option_value = self::getOption($value['id'], $value['default']);

                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr($value['id']); ?>">
                                <?php echo esc_html($value['title']); ?>
                            </label>
                            <?php echo $tooltip_html; ?>
                        </th>
                        <td class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                            <input
                                name="<?php echo self::$prefix; ?>[<?php echo esc_attr($value['id']); ?>]"
                                id="<?php echo esc_attr($value['id']); ?>"
                                type="<?php echo esc_attr($type); ?>"
                                style="<?php echo esc_attr($value['css']); ?>"
                                value="<?php echo esc_attr($option_value); ?>"
                                class="<?php echo esc_attr($value['class']); ?>"
                                placeholder="<?php echo esc_attr($value['placeholder']); ?>"
                                <?php echo implode(' ', $custom_attributes); ?>
                                > <?php echo $description; ?>
                        </td>
                    </tr>
                    <?php
                    break;

                // Textarea
                case 'textarea':
                    $option_value = self::getOption($value['id'], $value['default']);

                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr($value['id']); ?>">
                            <?php echo esc_html($value['title']); ?></label>
                            <?php echo $tooltip_html; ?>
                        </th>
                        <td class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                            <?php echo $description; ?>

                            <textarea
                                name="<?php echo self::$prefix; ?>[<?php echo esc_attr($value['id']); ?>]"
                                id="<?php echo esc_attr($value['id']); ?>"
                                style="<?php echo esc_attr($value['css']); ?>"
                                class="<?php echo esc_attr($value['class']); ?>"
                                placeholder="<?php echo esc_attr($value['placeholder']); ?>"
                                <?php echo implode(' ', $custom_attributes); ?>
                                ><?php echo esc_textarea($option_value);  ?></textarea>
                        </td>
                    </tr>
                    <?php
                    break;

                // Select boxes
                case 'select':
                case 'multiselect':
                    $option_value = self::getOption($value['id'], $value['default']);

                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr($value['id']); ?>">
                                <?php echo esc_html($value['title']); ?>
                            </label>
                            <?php echo $tooltip_html; ?>
                        </th>
                        <td class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                            <select name="<?php echo self::$prefix; ?>[<?php echo esc_attr($value['id']); ?>]<?php if ($value['type'] === 'multiselect') { echo '[]'; } ?>"
                                id="<?php echo esc_attr($value['id']); ?>"
                                style="<?php echo esc_attr($value['css']); ?>"
                                class="<?php echo esc_attr($value['class']); ?>"
                                <?php echo implode(' ', $custom_attributes); ?>
                                <?php echo ('multiselect' === $value['type']) ? 'multiple="multiple"' : ''; ?>>
                                <?php foreach ($value['options'] as $key => $val) {?>
                                    <option value="<?php echo esc_attr($key); ?>"
                                    <?php

                                    if (is_array($option_value)) {
                                        selected(
                                            in_array((string) $key, array_map('sanitize_text_field', $option_value), true),
                                            true
                                        );
                                    } else {
                                        selected($option_value, $key);
                                    } ?>>
                                    <?php echo $val ?></option>
                                <?php } ?>
                            </select> <?php echo $description; ?>
                        </td>

                    </tr>
                    <?php
                    break;

                // Radio inputs
                case 'radio':
                    $option_value = self::getOption($value['id'], $value['default']);

                    ?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr($value['id']); ?>">
                                <?php echo esc_html($value['title']); ?>
                            </label>
                            <?php echo $tooltip_html; ?>
                        </th>
                        <td class="forminp forminp-<?php echo sanitize_title($value['type']) ?>">
                            <fieldset>
                                <?php echo $description; ?>
                                <ul>
                                <?php
                                foreach ($value['options'] as $key => $val) { ?>
                                    <li>
                                        <label>
                                        <input
                                            name="<?php echo self::$prefix; ?>[<?php echo esc_attr($value['id']); ?>]"
                                            value="<?php echo $key; ?>"
                                            type="radio"
                                            style="<?php echo esc_attr($value['css']); ?>"
                                            class="<?php echo esc_attr($value['class']); ?>"
                                            <?php echo implode(' ', $custom_attributes); ?>
                                            <?php checked($key, $option_value); ?>
                                            > <?php echo $val ?>
                                        </label>
                                    </li>
                                <?php
                                } ?>
                                </ul>
                            </fieldset>
                        </td>
                    </tr>
                    <?php
                    break;

                // Checkbox input
                case 'checkbox':
                    $option_value    = self::getOption($value['id'], $value['default']);
                    $visbility_class = array();

                    if (! isset($value['hide_if_checked'])) {
                        $value['hide_if_checked'] = false;
                    }
                    if (! isset($value['show_if_checked'])) {
                        $value['show_if_checked'] = false;
                    }
                    if ('yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked']) {
                        $visbility_class[] = 'hidden_option';
                    }
                    if ('option' === $value['hide_if_checked']) {
                        $visbility_class[] = 'hide_options_if_checked';
                    }
                    if ('option' === $value['show_if_checked']) {
                        $visbility_class[] = 'show_options_if_checked';
                    }

                    if (! isset($value['checkboxgroup']) || 'start' === $value['checkboxgroup']) {
                        ?>
                            <tr valign="top" class="<?php echo esc_attr(implode(' ', $visbility_class)); ?>">
                                <th scope="row" class="titledesc"><?php echo esc_html($value['title']) ?></th>
                                    <td class="forminp forminp-checkbox">
                                        <fieldset>
                    <?php

                    } else {
                        ?>
                        <fieldset class="<?php echo esc_attr(implode(' ', $visbility_class)); ?>">
                        <?php
                    }

                    if (! empty($value['title'])) {
                        ?>
                        <legend class="screen-reader-text"><span><?php echo esc_html($value['title']) ?></span></legend>
                        <?php
                    }

                    ?>
                        <label for="<?php echo $value['id'] ?>">
                        <input
                            name="<?php echo self::$prefix; ?>[<?php echo esc_attr($value['id']); ?>]"
                            id="<?php echo esc_attr($value['id']); ?>"
                            type="checkbox"
                            class="<?php echo esc_attr(isset($value['class']) ? $value['class'] : ''); ?>"
                            value="1"
                            <?php checked($option_value, 'yes'); ?>
                            <?php echo implode(' ', $custom_attributes); ?>> <?php echo $description ?>
                        </label> <?php echo $tooltip_html; ?>
                    <?php

                    if (! isset($value['checkboxgroup']) || 'end' === $value['checkboxgroup']) {
                        ?>
                                    </fieldset>
                                </td>
                            </tr>
                        <?php

                    } else {
                        ?>
                            </fieldset>
                        <?php

                    }
                    break;

                // Default: run an action
                default:
                    do_action('convertiser_widgets_setting_field_' . $value['type'], $value);
                    break;
            }
        }
    }

    /**
     * Helper function to get the formatted description and tip HTML for a
     * given form field. Plugins can call this when implementing their own custom
     * settings types.
     *
     * @param array $value The form field value array
     *
     * @returns array The description and tip as a 2 element array
     */
    public static function getFieldDescription($value)
    {
        $description  = '';
        $tooltip_html = '';

        if (true === $value['desc_tip']) {
            $tooltip_html = $value['desc'];
        } elseif (! empty($value['desc_tip'])) {
            $description  = $value['desc'];
            $tooltip_html = $value['desc_tip'];
        } elseif (! empty($value['desc'])) {
            $description  = $value['desc'];
        }

        if ($description && in_array($value['type'], array( 'textarea', 'radio' ), true)) {
            $description = '<p style="margin-top:0">' . wp_kses_post($description) . '</p>';
        } elseif ($description && 'checkbox' === $value['type']) {
            $description = wp_kses_post($description);
        } elseif ($description) {
            $description = '<span class="description">' . wp_kses_post($description) . '</span>';
        }

        if ($tooltip_html && 'checkbox' === $value['type']) {
            $tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
        } elseif ($tooltip_html) {
            $tooltip_html = sprintf(
                '<img class="help_tip" title="%s" src="%s" height="16" width="16">',
                esc_attr($tooltip_html),
                getPlugin()->getUrl('/assets/images/help.png')
            );
        }

        return array(
            'description'  => $description,
            'tooltip_html' => $tooltip_html
        );
    }

    /**
     * Save admin fields.
     *
     * Loops though the convertiser options array, sanitizes and saves state to db.
     *
     * @param array $input
     *
     * @return bool
     */
    public static function sanitizeFields($input)
    {
        if (null === $input || !is_array($input) || empty($input)) {
            $input = array();
        }

        // Get current tab/section
        $current_tab =  isset($_POST['tab']) ? sanitize_text_field($_POST['tab']) : '';

        // Options to update will be stored here and saved later
        $update_options = array();

        // Load current state of setting fields
        $saved_options = get_option(self::$prefix, array());

        // Grab options that are available on this screen.
        $options = self::getAvailableOptions($current_tab);

        // Loop options and get values to save
        foreach ($options as $option) {
            if (! isset($option['id'], $option['type'])
            || in_array($option['type'], array( 'title', 'sectionend' ), true)) {
                continue;
            }

            // Get posted value
            $option_name  = $option['id'];
            $raw_value    = isset($input[ $option['id'] ]) ? wp_unslash($input[ $option['id'] ]) : null;

            // Format the value based on option type
            switch ($option['type']) {
                case 'checkbox':
                    $value = null === $raw_value ? 'no' : 'yes';
                    break;
                case 'textarea':
                    $value = wp_kses_post(trim($raw_value));
                    break;
                case 'multiselect':
                    $value = array_filter(array_map('sanitize_text_field', (array) $raw_value));
                    break;
                default:
                    $value = sanitize_text_field($raw_value);
                    break;
            }

            /**
             * Sanitize the value of an option
             */
            $value = apply_filters('convertiser_widgets_settings_sanitize_option', $value, $option, $raw_value);

            /**
             * Sanitize the value of an option by option name
             */
            $value = apply_filters(
                "convertiser_widgets_settings_sanitize_option_$option_name",
                $value,
                $option,
                $raw_value
            );

            $update_options[ $option_name ] = $value;
        }

//        self::addMessage(__('Your settings have been saved.', 'convertiser-widgets'));
        return array_merge($saved_options, $update_options);
    }
}
