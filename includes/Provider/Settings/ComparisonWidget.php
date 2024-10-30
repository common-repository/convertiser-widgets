<?php
/**
 * Comparison Widget Settings
 */
namespace Convertiser\Widgets\Provider\Settings;

class ComparisonWidget extends Page
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id    = 'comparison';
        $this->label = __('Price Comparison Widget', 'convertiser-widgets');
        $this->registerHooks();
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function getSettings()
    {
        $fonts = array(
            'thin'   => __('Thin', 'convertiser-widgets'),
            'normal' => __('Normal', 'convertiser-widgets'),
            'bold'   => __('Bold', 'convertiser-widgets'),
            'bolder' => __('Bolder', 'convertiser-widgets'),
        );

        $colors = array(
            '#F44336' => __('red', 'convertiser-widgets'),
            '#E91E63' => __('pink', 'convertiser-widgets'),
            '#9C27B0' => __('purple', 'convertiser-widgets'),
            '#673AB7' => __('deep-purple', 'convertiser-widgets'),
            '#3F51B5' => __('indigo', 'convertiser-widgets'),
            '#2196F3' => __('blue', 'convertiser-widgets'),
            '#333333' => __('black', 'convertiser-widgets'),
            '#03A9F4' => __('light-blue', 'convertiser-widgets'),
            '#00BCD4' => __('cyan', 'convertiser-widgets'),
            '#009688' => __('teal', 'convertiser-widgets'),
            '#4CAF50' => __('green', 'convertiser-widgets'),
            '#8BC34A' => __('light-green', 'convertiser-widgets'),
            '#CDDC39' => __('lime', 'convertiser-widgets'),
            '#FFEB3B' => __('yellow', 'convertiser-widgets'),
            '#FFC107' => __('amber', 'convertiser-widgets'),
            '#FF9800' => __('orange', 'convertiser-widgets'),
            '#FF5722' => __('deep-orange', 'convertiser-widgets'),
            '#795548' => __('brown', 'convertiser-widgets'),
            '#808080' => __('grey', 'convertiser-widgets'),
            '#607D8B' => __('blue-grey', 'convertiser-widgets'),
        );

        $limit = array(
            0 => __('Unlimited', 'convertiser-widgets'),
        );

        for ($i = 1; $i <= 20; $i++) {
            $limit[$i] = $i;
        }


        $settings = apply_filters('convertiser_widgets_comparison_settings', array(

            array(
                'title' => __('Price Comparison Widget Settings', 'convertiser-widgets'),
                'type'  => 'title',
                'desc'  => __('Configure default behaviour for price comparison widget.', 'convertiser-widgets'),
                'id'    => 'comparison_settings'
            ),

            array(
                'title'    => __('Enable widget', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_enabled',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Enable WooCommerce integration', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_woocommerce_integration',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Leading text', 'convertiser-widgets'),
                'desc'     => __(
                    'Default leading text with optional HTML tags to show above price offers table. 
                    There is a `{product_title}` placeholder available that 
                    would be replaced with actual product title.',
                    'convertiser-widgets'
                ),
                'id'       => 'comparison_lead_text',
                'class'    => 'regular-text',
                'default'  => 'Check current prices for {product_title}',
                'type'     => 'textarea',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('No offers for product', 'convertiser-widgets'),
                'desc'     => __(
                    'Custom message that would be shown if no price offers are available for requested product.',
                    'convertiser-widgets'
                ),
                'id'       => 'comparison_no_offers_text',
                'class'    => 'regular-text',
                'default'  => 'There are no price offers for this product at the moment.',
                'type'     => 'textarea',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Show product gallery', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_gallery',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Show borders', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_borders',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Show retailer logo', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_retailer_logo',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Retailer name font color', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_retailer_font_color',
                'desc_tip' => true,
                'default'  => '#607D8B',
                'type'     => 'select',
                'options'  => $colors,
            ),

            array(
                'title'    => __('Retailer name font weight', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_retailer_font_weight',
                'desc_tip' => true,
                'default'  => 'bold',
                'type'     => 'select',
                'options'  => $fonts,
            ),

            array(
                'title'    => __('Price font color', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_price_font_color',
                'desc_tip' => true,
                'default'  => '#4CAF50',
                'type'     => 'select',
                'options'  => $colors,
            ),

            array(
                'title'    => __('Price font weight', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_price_font_weight',
                'desc_tip' => true,
                'default'  => 'bolder',
                'type'     => 'select',
                'options'  => $fonts,
            ),

            array(
                'title'    => __('Limit results', 'convertiser-widgets'),
                'desc'     => __('Optionally limit number of price offers.', 'convertiser-widgets'),
                'id'       => 'comparison_limits',
                'desc_tip' => true,
                'default'  => 0,
                'type'     => 'select',
                'options'  => $limit,
            ),

            array(
                'title'    => __('Order results by price', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'comparison_ordering',
                'desc_tip' => true,
                'default'  => 'desc',
                'type'     => 'select',
                'options'  => array(
                    'asc'  => __('Ascending', 'convertiser-widgets'),
                    'desc' => __('Descending', 'convertiser-widgets'),
                ),
            ),

            array('type' => 'sectionend', 'id' => 'comparison_settings'),

        ));

        return apply_filters('convertiser_widgets_get_settings_' . $this->id, $settings);
    }
}
