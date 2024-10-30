<?php
/**
 * Shortcake Field: Comparison Widget Preview
 */

namespace Convertiser\Widgets\Provider\Widgets\Shortcake\Field;

use function Convertiser\Widgets\getPlugin;
use Convertiser\Widgets\Provider\Settings;
use Shortcode_UI;
use WP_Error;

class RecommendationWidgetSelect
{
    private static $instance;

    private $api = 'https://api.convertiser.com/publisher/adslots/find/';

    // Field Settings.
    private $fields = array(
        'recommendation_widget_select' => array(
            'template' => 'shortcode-ui-field-recommendation-widget-select',
            'view'     => 'editAttributeFieldRecommendationWidgetSelect',
        ),
    );

    /**
     * Setup the instance.
     *
     * @return RecommendationWidgetSelect
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self;
            self::$instance->setupActions();
        }

        return self::$instance;
    }

    /**
     * Add the required actions and filters.
     */
    private function setupActions()
    {
        add_filter('shortcode_ui_fields', array($this, 'filterShortcodeUIFields'));
        add_action('enqueue_shortcode_ui', array($this, 'actionEnqueueShortcodeUI'));
        add_action('wp_ajax_shortcode_ui_recommendation_widget_field', array($this, 'ajaxHandler'));
        add_action('shortcode_ui_loaded_editor', array($this, 'actionShortcodeUILoadedEditor'));
    }

    /**
     * Add our field to the shortcode fields.
     *
     * @param $fields
     *
     * @return array
     */
    public function filterShortcodeUIFields($fields)
    {
        return array_merge($fields, $this->fields);
    }

    /**
     * Add Select2 for our UI.
     */
    public function actionEnqueueShortcodeUI()
    {
        wp_enqueue_script(Shortcode_UI::$select2_handle);
        wp_enqueue_style(Shortcode_UI::$select2_handle);

        wp_localize_script('shortcode-ui', 'shortcodeUiRecommendationWidgetFieldData', array(
            'nonce' => wp_create_nonce('recommendationWidgetSelect')
        ));

        wp_enqueue_script(
            'recommendation-widget-sui',
            getPlugin()->getUrl('/assets/js/shortcake/recommendation-widget-select.min.js'),
            array('jquery', 'shortcode-ui'),
            getPlugin()->getVersion()
        );
    }

    /**
     * Output styles and templates used by recommendation widget select field.
     */
    public function actionShortcodeUILoadedEditor()
    {
        ?>

        <script type="text/html" id="tmpl-shortcode-ui-field-recommendation-widget-select">
            <div
                class="field-block shortcode-ui-field-recommendation-widget-select shortcode-ui-attribute-{{ data.attr }}">
                <label for="{{ data.id }}">{{{ data.label }}}</label>
                <select name="{{ data.attr }}" id="{{ data.id }}"
                        class="shortcode-ui-recommendation-widget-select"></select>
                <# if ( typeof data.description == 'string' && data.description.length ) { #>
                    <p class="description">{{{ data.description }}}</p>
                    <# } #>
            </div>
        </script>

        <?php

    }

    /**
     * Ajax handler for select2 recommendation widget field queries.
     * Output JSON containing adslot data.
     * Requires that shortcode, attr and nonce are passed.
     * Requires that the field has been correctly registered and can be found in $this->post_fields
     * Supports passing page number and search query string.
     *
     * @return null
     */
    public function ajaxHandler()
    {
        $token               = Settings::getOption('token', '');
        $page_size           = 10;
        $nonce               = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : null;
        $requested_shortcode = isset($_GET['shortcode']) ? sanitize_text_field(wp_unslash($_GET['shortcode'])) : null;
        $requested_attr      = isset($_GET['attr']) ? sanitize_text_field(wp_unslash($_GET['attr'])) : null;
        $page                = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : null;
        $search_str          = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : null;
        $response            = array('items' => array(), 'found_items' => 0, 'items_per_page' => 0);

        $include = null;
        if (isset($_GET['include'])) {
            // Make sure include is always an array & sanitize its values.
            $include = is_array($_GET['include']) ? $_GET['include'] : explode(',', $_GET['include']);
            $include = array_filter(array_map('absint', $include));
        }

        if (! wp_verify_nonce($nonce, 'recommendationWidgetSelect')) {
            wp_send_json_error($response);
        }

        $shortcodes = Shortcode_UI::get_instance()->get_shortcodes();

        // Shortcode not found.
        if (! isset($shortcodes[$requested_shortcode])) {
            wp_send_json_error($response);
        }

        $shortcode = $shortcodes[$requested_shortcode];


        // Create request uri
        $url_parts = parse_url($this->api);
        parse_str($url_parts['query'], $request_params);

        $request_params['title']     = $search_str;
        $request_params['page']      = $page;
        $request_params['page_size'] = $page_size;

        // Include selected widgets to be displayed.
        if ($include) {
            $request_params['id'] = implode(',', $include);
        }

        $url_parts['query'] = http_build_query($request_params);
        $api_url = $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['path'] . '?' . $url_parts['query'];


        /** @var array|WP_Error $response */
        $request = wp_remote_get(
            $api_url,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Token ' . $token,
                ),
            )
        );

        if (is_wp_error($request)) {
            wp_send_json_error($response);
        }

        $adslots = json_decode(wp_remote_retrieve_body($request));

        foreach ($adslots->results as $adslot) {
            $response['items'][] = array(
                'id'   => $adslot->id,
                'text' => html_entity_decode($adslot->title),
            );
        }

        $response['found_items']    = $adslots->count;
        $response['items_per_page'] = $page_size;

        wp_send_json_success($response);
    }
}
