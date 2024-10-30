<?php
/**
 * Recommendations Widget Settings
 */
namespace Convertiser\Widgets\Provider\Settings;

class RecommendationsWidget extends Page
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id    = 'recommendations';
        $this->label = __('Products Recommendations Widget', 'convertiser-widgets');
        $this->registerHooks();
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function getSettings()
    {
        $settings = apply_filters('convertiser_widgets_recommendations_settings', array(

            array(
                'title' => __('Products Recommendations Widget Settings', 'convertiser-widgets'),
                'type'  => 'title',
                'desc'  => __(
                    'Configure default behaviour for products recommendations widget.',
                    'convertiser-widgets'
                ),
                'id'    => 'recommendations_settings',
            ),

            array(
                'title'    => __('Enable widget', 'convertiser-widgets'),
                'desc'     => '',
                'id'       => 'recommendations_enabled',
                'default'  => 'yes',
                'type'     => 'checkbox',
                'desc_tip' => true,
            ),

            array('type' => 'sectionend', 'id' => 'recommendations_settings'),

        ));

        return apply_filters('convertiser_widgets_get_settings_' . $this->id, $settings);
    }
}
