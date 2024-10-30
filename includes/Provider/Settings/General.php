<?php
/**
 * General Settings
 */
namespace Convertiser\Widgets\Provider\Settings;

class General extends Page
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id    = 'general';
        $this->label = __('General', 'convertiser-widgets');
        $this->registerHooks();
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function getSettings()
    {
        $settings = apply_filters('convertiser_widgets_general_settings', array(

            array(
                'title' => __('Website Settings', 'convertiser-widgets'),
                'type'  => 'title',
                'desc'  => __('Setup website access to Convertiser.', 'convertiser-widgets'),
                'id'    => 'access_settings'
            ),

            array(
                'title'    => __('API Token', 'convertiser-widgets'),
                'desc'     => __('API Token can be created at your Convertiser account.', 'convertiser-widgets'),
                'id'       => 'token',
                'class'    => 'regular-text',
                'default'  => '',
                'type'     => 'text',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Website GUID', 'convertiser-widgets'),
                'desc'     => __(
                    'Website GUID is available on website details page at your Convertiser account.',
                    'convertiser-widgets'
                ),
                'id'       => 'website_guid',
                'class'    => 'regular-text',
                'default'  => '',
                'type'     => 'text',
                'desc_tip' => true,
            ),

            array(
                'title'    => __('Display Verification Code', 'convertiser-widgets'),
                'desc'     => __(
                    'Automatically displays verification code to 
                    simplify domain ownership verification as Convertiser.',
                    'convertiser-widgets'
                ),
                'id'       => 'display_verification_code',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array('type' => 'sectionend', 'id' => 'access_settings'),

        ));

        return apply_filters('convertiser_widgets_get_settings_' . $this->id, $settings);
    }
}
