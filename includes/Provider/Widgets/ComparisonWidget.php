<?php
/**
 * Convertiser Price Comparison Widget
 */

namespace Convertiser\Widgets\Provider\Widgets;

use Convertiser\Widgets\AbstractProvider;
use Convertiser\Widgets\Provider\Settings;
use WP_Post;

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class ComparisonWidget extends AbstractProvider
{
    public static $cache_key = '_convertiser_widgets_price_comparison';

    public static $api_url = 'https://converti.se/comparison/';

    public function registerHooks()
    {

        // Register Shortcode
        add_shortcode('convertiser_comparison', array($this, 'registerShortcode'));

        // Shortcake UI integration
        add_action('register_shortcode_ui', array($this, 'registerShortcodeUI'));

        # WooCommerce integration
        if ($this->isEnabled() && $this->wcIntegrationEnabled()) {
            add_filter('woocommerce_product_tabs', array($this, 'woocommerceDefaultProductTabs'));
            add_action('convertiser_widgets_update_price_comparison_cache', array(
                $this,
                'updatePriceComparison',
            ), 10, 2);
            add_action('post_updated', array($this, 'onProductTitleUpdate'), 10, 3);
            add_action('update_postmeta', array($this, 'onProductMpnUpdate'), 10, 4);
        }
    }

    /**
     * Tests whether plugin is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return Settings::getOption('comparison_enabled') === 'yes';
    }

    /**
     * Tests WC integration
     * @return bool
     */
    public function wcIntegrationEnabled()
    {
        return Settings::getOption('comparison_woocommerce_integration') === 'yes';
    }

    /**
     * Schedule price comparison data update if product title field has been changed
     *
     * @param int $post_id The post ID.
     * @param WP_POST|int $post_after The post object.
     * @param WP_POST|int $post_before The post object.
     *
     * @return void;
     */
    public function onProductTitleUpdate($post_id, $post_after, $post_before)
    {
        if (get_post_type($post_after) !== 'product') {
            return;
        }

        if ($post_before->post_title !== $post_after->post_title) {
            $this->schedulePriceComparisonUpdate($post_id, true);
        }
    }

    /**
     * Schedule price comparison data update if product MPN field has been changed
     *
     * @param $meta_id
     * @param $object_id
     * @param $meta_key
     * @param $meta_value
     *
     * @return void
     */
    public function onProductMpnUpdate($meta_id, $object_id, $meta_key, $meta_value)
    {
        if ('mpn' === $meta_key &&
            get_post_type($object_id) === 'product' &&
            get_post_meta($object_id, $meta_key, true) !== $meta_value
        ) {
            $this->schedulePriceComparisonUpdate($object_id, true);
        }
    }


    /**
     * Add default product tabs to product pages.
     *
     * @param array $tabs
     *
     * @return array
     */
    public function woocommerceDefaultProductTabs(array $tabs = array())
    {
        $tabs['price_comparison'] = array(
            'title'    => __('Compare Prices', 'convertiser-widgets'),
            'priority' => 0,
            'callback' => array($this, 'woocommercePriceComparisonTab'),
        );

        return $tabs;
    }

    /**
     * Update price comparison cache
     *
     * @param int $product_id
     * @param bool $force
     */
    public function updatePriceComparison($product_id, $force = false)
    {
        $product = wc_get_product($product_id);

        // deleted product OR update ttl is not reached
        if (null === $product || ! $this->shouldUpdatePriceComparison($product_id, $force)) {
            return;
        }

        $title = $product->post->post_title;
        $mpn   = get_post_meta($product->post->ID, 'mpn', true);

        $payload = array(
            'filters'  => array(
                array(
                    'title' => array('lookup' => 'dis_max', 'value' => $title),
                ),
            ),
            'key'      => Settings::getOption('website_guid'),
            'ordering' => 'asc',
        );

        if ($mpn) {
            $payload['filters'][] = array(
                'uni' => array('lookup' => 'contains', 'value' => $mpn),
            );
        }

        $args = array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
        );

        $response      = wp_remote_post(self::$api_url, $args);
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if (! in_array($response_code, array(200, 201), true) || is_wp_error($response_body)) {
            return;
        }

        $response_body = json_decode($response_body, true);

        $normalize_price = function ($price) {
            return preg_replace('/[^0-9,.]/', '', $price);
        };

        // calculate defaults
        $default_price = get_post_meta($product_id, '_price', true);
        $offers_cnt    = count($response_body['offers']) > 0 ? count($response_body['offers']) : 1;
        $lowest_price  = count($response_body['offers']) > 0 ? $response_body['offers'][0]['price'] : $default_price;
        $highest_price = count($response_body['offers']) > 0 ?
            $response_body['offers'][count($response_body['offers']) - 1]['price'] : $default_price;

        $data = array(
            'updated_at'    => time(),
            'data'          => $response_body,
            'lowest_price'  => $normalize_price($lowest_price),
            'highest_price' => $normalize_price($highest_price),
            'offers_count'  => $offers_cnt,
        );

        update_post_meta($product_id, self::$cache_key, $data);
    }

    /**
     * Tests whether price comparison data should be updated
     *
     * @param int $product_id
     * @param bool $force
     *
     * @return bool
     */
    public function shouldUpdatePriceComparison($product_id, $force = false)
    {
        if (! $this->isEnabled() || ! $this->wcIntegrationEnabled() || ! $this->plugin->isConfigured()) {
            return false;
        }

        $cache_ttl        = 86400;
        $update_cache     = true;
        $price_comparison = maybe_unserialize(get_post_meta($product_id, self::$cache_key, true));
        if ($price_comparison) {
            $updated_at = isset($price_comparison['updated_at']) ? $price_comparison['updated_at'] : time();

            if (! $force && (time() - $updated_at) <= $cache_ttl) {
                $update_cache = false;
            }
        }

        return $update_cache;
    }

    /**
     * Schedule price comparison cache update
     *
     * @param int $product_id
     * @param bool $force
     */
    public function schedulePriceComparisonUpdate($product_id, $force = false)
    {
        if ($this->shouldUpdatePriceComparison($product_id, $force)) {
            wp_schedule_single_event(time(), 'convertiser_widgets_update_price_comparison_cache', array(
                $product_id,
                $force,
            ));
        }
    }

    /**
     * Display price comparison table
     */
    public function woocommercePriceComparisonTab()
    {
        global $product;

        # cache price comparison results for future needs
        $this->schedulePriceComparisonUpdate($product->post->ID);

        $atts = array('title' => $product->post->post_title);

        # MPN
        $mpn = get_post_meta($product->post->ID, 'mpn', true);
        if ($mpn) {
            $atts['mpn'] = $mpn;
        }

        $out = $this->registerShortcode($atts);
        echo $out;
    }

    /**
     * Comparison widget: shortcode
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

        if (! $this->isEnabled() || ! $this->plugin->isConfigured()) {
            return '';
        }

        $atts = array_change_key_case((array)$atts, CASE_LOWER);
        $atts = shortcode_atts(array(
            'title'               => '',
            'mpn'                 => '',
            'ordering'            => Settings::getOption('comparison_ordering'),
            'limit'               => Settings::getOption('comparison_limits'),
            'gallery'             => Settings::getOption('comparison_gallery'),
            'card'                => Settings::getOption('comparison_borders'),
            'offer_logo'          => Settings::getOption('comparison_retailer_logo'),
            'offer_weight'        => Settings::getOption('comparison_retailer_font_weight'),
            'offer_color'         => Settings::getOption('comparison_retailer_font_color'),
            'price_weight'        => Settings::getOption('comparison_price_font_weight'),
            'price_color'         => Settings::getOption('comparison_price_font_color'),
            'hide_lead_text'      => 'yes',
            'hide_no_offers_text' => 'yes',
        ), $atts);

        // normalize values
        foreach ($atts as $key => $val) {
            $val        = trim($val);
            $atts[$key] = $val;

            switch ($key) {
                case 'hide_lead_text':
                case 'hide_no_offers_text':
                case 'gallery':
                case 'card':
                case 'offer_logo':
                    $val        = in_array($val, array('yes', '1', 'true'), true) ? true : false;
                    $atts[$key] = $val;
                    break;
                case 'offer_weight':
                case 'price_weight':
                    $val        = in_array($val, array(
                        'thin',
                        'normal',
                        'bold',
                        'bolder',
                    ), true) ? $val : 'normal';
                    $atts[$key] = $val;
                    break;
                case 'offer_color':
                case 'price_color':
                    //Check for a hex color string without hash 'c1c2b4'
                    $val = str_replace('#', '', $val);
                    if (preg_match('/^[a-f0-9]{6}$/i', $val)) {
                        $atts[$key] = '#' . $val;
                    } else {
                        if ('price_color' === $key) {
                            $atts[$key] = '#' . '4CAF50';
                        } else {
                            $atts[$key] = '#' . '607D8B';
                        }
                    }
                    break;
                case 'ordering':
                    $val        = in_array($val, array('asc', 'desc'), true) ? $val : 'desc';
                    $atts[$key] = $val;
                    break;
                case 'limit':
                    $val        = (int)$val;
                    $val        = $val > 0 ? $val : null;
                    $atts[$key] = $val;
                    break;
            }
        }

        // misconfiguration
        if (empty($atts['title']) && empty($atts['mpn'])) {
            return '';
        }

        $code = <<<HTML
<script type="text/javascript">// <![CDATA[
(function(win, doc) {
win.convertiser_comparison_cfg = win.convertiser_comparison_cfg || {};
win.convertiser_comparison_cfg['%rand%'] = {"key":%key%,"selector":"","selector_uni":"","keywords":%title%,"uni":%mpn%,"ordering":%ordering%,"limit":%limit%,"theme":{"gallery":%gallery%,"card":%card%,"offer_logo":%offer_logo%,"price":{"weight":%price_weight%,"color":%price_color%},"offer":{"weight":%offer_weight%,"color":%offer_color%}}, callbacks:%callbacks%};

var script = doc.createElement('script');
script.type = 'text/javascript';
script.async = true;
script.charset = 'utf-8';
script.className = 'comparison-script';
script.setAttribute('data-id', '%rand%');
script.src =
'//converti.se/comparison.js';
var s = doc.getElementsByTagName('script')[0];
s.parentNode.insertBefore(script, s);
})(window, document);
// ]]></script>%no_offers_text%%lead_text%
<ins class="convertiser_comparison" data-id="convertiser_comparison_%rand%"></ins>
HTML;

        $widget_id = random_int(100000, 1000000);
        $code      = str_replace('%key%', wp_json_encode(Settings::getOption('website_guid')), $code);
        $code      = str_replace('%rand%', 'ct_' . $widget_id, $code);
        $code      = str_replace('%title%', wp_json_encode($atts['title']), $code);
        $code      = str_replace('%mpn%', wp_json_encode($atts['mpn']), $code);
        $code      = str_replace('%ordering%', wp_json_encode($atts['ordering']), $code);
        $code      = str_replace('%limit%', wp_json_encode($atts['limit']), $code);
        $code      = str_replace('%gallery%', wp_json_encode($atts['gallery']), $code);
        $code      = str_replace('%card%', wp_json_encode($atts['card']), $code);
        $code      = str_replace('%offer_logo%', wp_json_encode($atts['offer_logo']), $code);
        $code      = str_replace('%offer_weight%', wp_json_encode($atts['offer_weight']), $code);
        $code      = str_replace('%offer_color%', wp_json_encode($atts['offer_color']), $code);
        $code      = str_replace('%price_weight%', wp_json_encode($atts['price_weight']), $code);
        $code      = str_replace('%price_color%', wp_json_encode($atts['price_color']), $code);

        # NO OFFERS MESSAGE
        $msg       = trim(is_admin() ? __(
            '<strong>Price Comparison Widget:</strong> There are no price offers for this product at the moment!',
            'convertiser-widgets'
        ) : Settings::getOption('comparison_no_offers_text'));
        $no_offers = sprintf(
            '<div style="display:none;" id="convertiser_comparison_notice_%s">%s</div>',
            $widget_id,
            $msg
        );

        # no offers message might be optionally hidden, but only outside of admin area
        if ($atts['hide_no_offers_text'] && ! is_admin()) {
            $code = str_replace('%no_offers_text%', '', $code);
        } else {
            $code = str_replace('%no_offers_text%', $no_offers, $code);
        }

        # LEAD MESSAGE
        $lead = sprintf(
            '<div style="display: none;" id="convertiser_comparison_lead_text_%s">%s</div>',
            $widget_id,
            trim(Settings::getOption('comparison_lead_text'))
        );
        $lead = str_replace('{product_title}', $atts['title'], $lead);

        # lead text might be optionally hidden
        if ($atts['hide_lead_text']) {
            $code = str_replace('%lead_text%', '', $code);
        } else {
            $code = str_replace('%lead_text%', $lead, $code);
        }


        # Callbacks
        $widget_callbacks = <<<JS
{
    onComplete: function(widget_id, element, response) {        
        if(!response['offers'].length){
            var el = document.getElementById('convertiser_comparison_notice_$widget_id');
            if (el) {
                el.style.display = 'initial';
            }
        }
    },
    onBeforeShow: function(widget_id, element, response) {
        if(response['offers'].length){
            var el = document.getElementById('convertiser_comparison_lead_text_$widget_id');
            if (el) {
                el.style.display = 'initial';
            }
        }
    }
}
JS;

        $code = str_replace('%callbacks%', $widget_callbacks, $code);

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

        $limit = array(
            0 => __('Unlimited', 'convertiser-widgets'),
        );

        for ($i = 1; $i <= 20; $i++) {
            $limit[$i] = $i;
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
                'label' => esc_html__('Hide lead text', 'convertiser-widgets'),
                'attr'  => 'hide_lead_text',
                'type'  => 'checkbox',
                'value' => false,
            ),
            array(
                'label' => esc_html__('Hide `no offers` text', 'convertiser-widgets'),
                'attr'  => 'hide_no_offers_text',
                'type'  => 'checkbox',
                'value' => false,
            ),
            array(
                'label'       => esc_html__('Product name', 'convertiser-widgets'),
                'description' => __(
                    'Provide accurate product name for price comparison widget.',
                    'convertiser-widgets'
                ),
                'attr'        => 'title',
                'type'        => 'text',
                'encode'      => false,
            ),
            array(
                'label'       => esc_html__('Product MPN (Optional)', 'convertiser-widgets'),
                'description' => __(
                    'Enter Manufacturer Product Number for better accuracy.',
                    'convertiser-widgets'
                ),
                'attr'        => 'mpn',
                'type'        => 'text',
                'encode'      => false,
            ),
            array(
                'label' => esc_html__('Show product gallery', 'convertiser-widgets'),
                'attr'  => 'gallery',
                'type'  => 'checkbox',
                'value' => Settings::getOption('comparison_gallery') === 'yes',
            ),
            array(
                'label' => esc_html__('Show borders', 'convertiser-widgets'),
                'attr'  => 'card',
                'type'  => 'checkbox',
                'value' => Settings::getOption('comparison_borders') === 'yes',
            ),
            array(
                'label' => esc_html__('Show retailer logo', 'convertiser-widgets'),
                'attr'  => 'offer_logo',
                'type'  => 'checkbox',
                'value' => Settings::getOption('comparison_retailer_logo') === 'yes',
            ),
            array(
                'label'  => esc_html__('Retailer name font color', 'convertiser-widgets'),
                'attr'   => 'offer_color',
                'type'   => 'color',
                'encode' => false,
                'value'  => Settings::getOption('comparison_retailer_font_color'),
                'meta'   => array(
                    'placeholder' => esc_html__('Hex color code', 'convertiser-widgets'),
                ),
            ),
            array(
                'label'   => esc_html__('Retailer name font weight', 'convertiser-widgets'),
                'attr'    => 'offer_weight',
                'type'    => 'select',
                'options' => array(
                    'thin'   => esc_html__('Thin', 'convertiser-widgets'),
                    'normal' => esc_html__('Normal', 'convertiser-widgets'),
                    'bold'   => esc_html__('Bold', 'convertiser-widgets'),
                    'bolder' => esc_html__('Bolder', 'convertiser-widgets'),
                ),
                'value'   => Settings::getOption('comparison_retailer_font_weight'),
            ),
            array(
                'label'  => esc_html__('Price font color', 'convertiser-widgets'),
                'attr'   => 'price_color',
                'type'   => 'color',
                'encode' => false,
                'value'  => Settings::getOption('comparison_price_font_color'),
                'meta'   => array(
                    'placeholder' => esc_html__('Hex color code', 'convertiser-widgets'),
                ),
            ),
            array(
                'label'   => esc_html__('Price font weight', 'convertiser-widgets'),
                'attr'    => 'price_weight',
                'type'    => 'select',
                'options' => array(
                    'thin'   => esc_html__('Thin', 'convertiser-widgets'),
                    'normal' => esc_html__('Normal', 'convertiser-widgets'),
                    'bold'   => esc_html__('Bold', 'convertiser-widgets'),
                    'bolder' => esc_html__('Bolder', 'convertiser-widgets'),
                ),
                'value'   => Settings::getOption('comparison_price_font_weight'),
            ),
            array(
                'label'   => esc_html__('Limit results', 'convertiser-widgets'),
                'attr'    => 'limit',
                'type'    => 'select',
                'options' => $limit,
                'value'   => Settings::getOption('comparison_limits'),
            ),
            array(
                'label'   => esc_html__('Order results by price', 'convertiser-widgets'),
                'attr'    => 'order',
                'type'    => 'select',
                'options' => array(
                    'asc'  => esc_html__('Ascending', 'convertiser-widgets'),
                    'desc' => esc_html__('Descending', 'convertiser-widgets'),
                ),
                'value'   => Settings::getOption('comparison_ordering'),
            ),
        );
        /*
         * Define the Shortcode UI arguments.
         */
        $shortcode_ui_args = array(
            /*
             * How the shortcode should be labeled in the UI. Required argument.
             */
            'label'         => esc_html__('Price Comparison', 'convertiser-widgets'),
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

        shortcode_ui_register_for_shortcode('convertiser_comparison', $shortcode_ui_args);
    }
}
