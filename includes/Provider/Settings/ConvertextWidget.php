<?php
/**
 * ConvertextWidget Widget Settings
 *
 */
namespace Convertiser\Widgets\Provider\Settings;

class ConvertextWidget extends Page
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id    = 'convertext';
        $this->label = __('Contextual Ads', 'convertiser-widgets');
        $this->registerHooks();
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function getSettings()
    {
        $settings = apply_filters('convertiser_widgets_convertext_settings', array(

            array(
                'title' => __('Contextual Ads Widget Settings', 'convertiser-widgets'),
                'type'  => 'title',
                'desc'  => __('Configure default behaviour for contextual ads widget.', 'convertiser-widgets'),
                'id'    => 'convertext_settings'
            ),

            array(
                'title'    => __('Enable widget', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'convertext_enabled',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Automated links affiliation', 'convertiser-widgets'),
                'desc'     => __(
                    'Convertiser scans your website content and detects links that leads to a products or merchants 
                    from our portfolio and converts them into affiliated links.',
                    'convertiser-widgets'
                ),
                'id'       => 'convertext_modlinks',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Contextual links insertion', 'convertiser-widgets'),
                'desc'     => __(
                    'Convertiser detects merchants, brands and product references on your website and turns 
                    them into links that guides your users to the most relevant retailers where they can 
                    purchase the mentioned items.',
                    'convertiser-widgets'
                ),
                'id'       => 'convertext_modwords',
                'default'  => 'no',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Links density', 'convertiser-widgets'),
                'id'       => 'convertext_modwords_density',
                'desc'     => __(
                    'Link density is determined by the number of links added 
                    as a ratio of the amount of text on a page.',
                    'convertiser-widgets'
                ),
                'desc_tip' => true,
                'default'  => 'optimum',
                'type'     => 'select',
                'options'  => array(
                    'maximum' => __('Maximum', 'convertiser-widgets'),
                    'optimum' => __('Optimum', 'convertiser-widgets'),
                    'minimum' => __('Minimum', 'convertiser-widgets'),
                ),
            ),

            array('type' => 'sectionend', 'id' => 'convertext_settings'),

            # Advanced Settings
            array(
                'title' => __('Advanced Settings', 'convertiser-widgets'),
                'type'  => 'title',
                'desc'  => __(
                    'Warning! This are advanced settings. Do not change if you are not sure what you are doing.',
                    'convertiser-widgets'
                ),
                'id'    => 'convertext_advanced_settings'
            ),

            array(
                'title'    => __('Include by CSS class', 'convertiser-widgets'),
                'desc'     => __(
                    'Comma separated list of css classes which will be used to include site areas from widget.',
                    'convertiser-widgets'
                ),
                'id'       => 'convertext_positive_cls',
                'class'    => 'regular-text',
                'default'  => 'ctxt-target-node',
                'type'     => 'text',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Ignore by CSS class', 'convertiser-widgets'),
                'desc'     => __(
                    'Comma separated list of css classes which will be used to exclude site areas from widget.',
                    'convertiser-widgets'
                ),
                'id'       => 'convertext_negative_cls',
                'class'    => 'regular-text',
                'default'  => 'ctxt-skip',
                'type'     => 'text',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Ignore domains', 'convertiser-widgets'),
                'desc'     => __(
                    'Comma separated list of domains that will be ignored by automatic links affiliation.',
                    'convertiser-widgets'
                ),
                'id'       => 'convertext_ignore_domains',
                'class'    => 'regular-text',
                'default'  => '',
                'type'     => 'text',
                'desc_tip' => true,
            ),

            array('type' => 'sectionend', 'id' => 'convertext_advanced_settings'),
        ));

        return apply_filters('convertiser_widgets_get_settings_' . $this->id, $settings);
    }
}
