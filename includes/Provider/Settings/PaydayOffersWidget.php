<?php
/**
 * Payday Offers Widget Settings
 */
namespace Convertiser\Widgets\Provider\Settings;

class PaydayOffersWidget extends Page
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id    = 'payday_offers';
        $this->label = __('Payday Offers Widget', 'convertiser-widgets');
        $this->registerHooks();
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function getSettings()
    {
        $settings = apply_filters('convertiser_widgets_payday-offers_settings', array(

            array(
                'title' => __('Payday Offers Widget Settings', 'convertiser-widgets'),
                'type'  => 'title',
                'desc'  => __('Configure default behaviour for price comparison widget.', 'convertiser-widgets'),
                'id'    => 'payday_offers_settings'
            ),

            array(
                'title'    => __('Enable widget', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'payday_offers_enabled',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Display loan simulation by default', 'convertiser-widgets'),
                'desc'     => __(
                    'Required by Google AdWords rules. Not all templates support this option.',
                    'convertiser-widgets'
                ),
                'id'       => 'payday_offers_show_loan_simulation',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Enable Google Analytics Tracking', 'convertiser-widgets'),
                'desc'     => __(
                    'Enable advanced event tracking for offers. 
                    Requires Google Analytics to be installed on site.',
                    'convertiser-widgets'
                ),
                'id'       => 'payday_offers_google_analytics_enabled',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Capture Tracking Params', 'convertiser-widgets'),
                'desc'     => __(
                    'Enable advanced tracking used to build accurate conversion attribution reports. 
                    Incoming links should contain the following GET parameters: keyword, sid, tag1, tag2, tag3, 
                    tag4, tag5; Example: https://yourblog.com/?keyword=ad+keyword&sid=mysubacc&tag1=mytag&tag2=mytag2',
                    'convertiser-widgets'
                ),
                'id'       => 'payday_offers_traffic_capture_enabled',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),


            array('type' => 'sectionend', 'id' => 'payday_offers_settings'),

        ));

        return apply_filters('convertiser_widgets_get_settings_' . $this->id, $settings);
    }
}
