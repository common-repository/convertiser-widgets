<?php
/**
 * Convertiser Product Recommendation Widget
 */

namespace Convertiser\Widgets\Provider\Widgets;

use Convertiser\Widgets\AbstractProvider;
use Convertiser\Widgets\Provider\Settings;
use Convertiser\Widgets\Provider\Widgets\Shortcake\Field\RecommendationWidgetSelect;

class RecommendationsWidget extends AbstractProvider
{
    public function registerHooks()
    {
        // Register Shortcode
        add_shortcode('convertiser_recommendations', array($this, 'registerShortcode'));

        // Shortcake UI integration
        add_action('register_shortcode_ui', array($this, 'registerShortcodeUI'));
        add_action('init', array($this, 'initShortcakeUI'), 5);
    }

    /**
     * Load custom Shortcake Fields
     */
    public function initShortcakeUI()
    {
        RecommendationWidgetSelect::getInstance();
    }

    public function isEnabled()
    {
        return Settings::getOption('recommendations_enabled') === 'yes';
    }

    /**
     * Recommendation widget: shortcode
     *
     * @param array|string $atts
     * @param null $content
     * @param string $tag
     *
     * @return string
     * @throws \Exception
     */
    public function registerShortcode($atts = [], $content = null, $tag = '')
    {
        if(!$atts) {
            $atts = [];
        }
        if (!$this->isEnabled() || !$this->plugin->isConfigured()) {
            return '';
        }

        $atts = array_change_key_case((array)$atts, CASE_LOWER);
        $atts = shortcode_atts(array(
            'id' => null
        ), $atts);

        $atts['id'] = isset($atts['id']) ? trim($atts['id']) : '';
        if (empty($atts['id'])) {
            return '';
        }

        $code = '<script async data-config="%id%" data-key="%key%" src="//converti.se/recommendations.js"></script>';
        $code = str_replace(
            array('%key%', '%id%'),
            array(esc_attr(Settings::getOption('website_guid')), esc_attr($atts['id'])),
            $code
        );

        return $code;
    }

    /**
     * Shortcode UI setup for the shortcake_dev shortcode.
     *
     * It is called when the Shortcake action hook `register_shortcode_ui` is called.
     */
    public function registerShortcodeUI()
    {
        if (!$this->isEnabled() || !$this->plugin->isConfigured()) {
            return;
        }

        /*
         * Define the UI for attributes of the shortcode. Optional.
         *
         * In this demo example, we register multiple fields related to showing a quotation
         * - Attachment, Citation Source, Select Page, Background Color, Alignment and Year.
         *
         * If no UI is registered for an attribute, then the attribute will
         * not be editable through Shortcake's UI. However, the value of any
         * unregistered attributes will be preserved when editing.
         *
         * Each array must include 'attr', 'type', and 'label'.
         * * 'attr' should be the name of the attribute.
         * * 'type' options include: text, checkbox, textarea, radio, select, email,
         *     url, number, and date, post_select, attachment, color.
         * * 'label' is the label text associated with that input field.
         *
         * Use 'meta' to add arbitrary attributes to the HTML of the field.
         *
         * Use 'encode' to encode attribute data. Requires customization in shortcode callback to decode.
         *
         * Depending on 'type', additional arguments may be available.
         */
        $fields = array(
            array(
                'label'       => esc_html__('Select Widget', 'convertiser-widgets'),
                'description' => 'Select recommendation widget',
                'attr'        => 'id',
                'type'        => 'recommendation_widget_select',
                'encode'      => false,
            ),
        );
        /*
         * Define the Shortcode UI arguments.
         */
        $shortcode_ui_args = array(
            /*
             * How the shortcode should be labeled in the UI. Required argument.
             */
            'label'         => esc_html__('Product Recommendation', 'convertiser-widgets'),
            /*
             * Include an icon with your shortcode. Optional.
             * Use a dashicon, or full HTML (e.g. <img src="/path/to/your/icon" />).
             */
            'listItemImage' => 'dashicons-menu',
            /*
             * Limit this shortcode UI to specific posts. Optional.
             */
//            'post_type'     => array('post'),
            /*
             * Define the UI for attributes of the shortcode. Optional.
             *
             * See above, to where the the assignment to the $fields variable was made.
             */
            'attrs'         => $fields,
        );

        shortcode_ui_register_for_shortcode('convertiser_recommendations', $shortcode_ui_args);
    }
}
