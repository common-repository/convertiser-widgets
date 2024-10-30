<?php
/**
 * Payday Offers Widget
 */

namespace Convertiser\Widgets\Provider\Widgets;

use Convertiser\Widgets\AbstractProvider;
use function Convertiser\Widgets\getPlugin;
use function Convertiser\Widgets\Lib\clean;
use function Convertiser\Widgets\Lib\collect;
use function Convertiser\Widgets\Lib\filter_empty;
use function Convertiser\Widgets\Lib\format_subid;
use function Convertiser\Widgets\Lib\http_build_url;
use Convertiser\Widgets\Provider\Settings;
use Convertiser\Widgets\Provider\Widgets\Shortcake\Field\LocalSelect2Field;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


class PaydayOffersWidget extends AbstractProvider
{
    public static $tracking_cookie = '_convertiser_widgets_payday_offers';

    public static $cache_key = '_convertiser_widgets_payday_offers';

    public static $redirect_opt_key = '_convertiser_widgets_payday_offers_permalinks';

    public static $api_url = 'https://api.convertiser.com/publisher/payday-loans/';

    public function registerHooks()
    {
        // Setup & Clear cron tasks
        register_activation_hook($this->plugin->getFile(), array($this, 'onPluginActivation'));
        register_deactivation_hook($this->plugin->getFile(), array($this, 'onPluginDeactivation'));

        // Register Shortcode
        add_shortcode('convertiser_payday_offers', array($this, 'registerShortcode'));

        // Register assets
        add_action('wp_enqueue_scripts', array($this, 'addAssets'));

        // Cron
        add_action('convertiser_widgets_payday_offers_update', array($this, 'updateCache'));
        add_action('init', array($this, 'onInit'));

        // Shortcake UI integration
        add_action('register_shortcode_ui', array($this, 'registerShortcodeUI'));
        add_action('init', array($this, 'initShortcakeUI'), 5);

        add_action('shortcode_ui_after_do_shortcode', array($this, 'getShortcodeAssets'));

        // Register redirect rule
        $this->setupLinksCloaking();

        // Capture & Provide tracking params
        if (Settings::getOption('payday_offers_traffic_capture_enabled') === 'yes') {
            // should be static
            add_action('template_redirect', array(__CLASS__, 'captureTrackingParams'), 0);
            add_filter('convertiser_widgets_payday_offers_redirect_url', array(__CLASS__, 'applyTrackingParams'));
        }
    }

    /**
     * Load custom Shortcake Fields
     */
    public function initShortcakeUI()
    {
        LocalSelect2Field::getInstance();
    }

    /**
     * Checks cache status on init
     */
    public function onInit()
    {
        if (!$this->isEnabled() || !$this->plugin->isConfigured()) {
            return;
        }

        $this->setupCron();
        $offers = static::getOffers();
        if (!$offers) {
            $this->updateCache();
        }
    }

    /**
     * Cron setup
     */
    public function setupCron()
    {
        if (!wp_next_scheduled('convertiser_widgets_payday_offers_update')) {
            wp_schedule_event(time(), 'hourly', 'convertiser_widgets_payday_offers_update');
        }
    }

    /**
     * Custom actions on plugin activation
     */
    public function onPluginActivation()
    {
        $this->setupCron();
        flush_rewrite_rules(true);
    }

    /**
     * Cleanup on plugin deactivation
     */
    public function onPluginDeactivation()
    {
        wp_clear_scheduled_hook('convertiser_widgets_payday_offers_update');
        flush_rewrite_rules(true);
    }

    /**
     * Tests whether plugin is enabled
     * @return bool
     */
    public function isEnabled()
    {
        return Settings::getOption('payday_offers_enabled') === 'yes';
    }

    /**
     * Retrieves payday offers from cache
     * @return array
     */
    public static function getOffers()
    {
        $cached = get_option(static::$cache_key);
        $offers = isset($cached['data']) ? json_decode($cached['data'], true) : array();
        return apply_filters('convertiser_widgets_payday_offers_get_offers', $offers);
    }

    /**
     * Update list of payday offers in cache
     */
    public function updateCache()
    {
        $guid    = Settings::getOption('website_guid', '');
        $api_url = rtrim(static::$api_url, '/') . '/' . $guid;

        $response = wp_remote_get(
            $api_url,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type'  => 'application/json',
                ),
            )
        );

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if (!in_array($response_code, array(200, 201), true) || is_wp_error($response_body)) {
            return;
        }

        $data = array(
            'updated_at' => time(),
            'data'       => $response_body,
        );

        update_option(static::$cache_key, $data);
        do_action('convertiser_widgets_payday_offers_updated', $data);
    }

    public function addAssets()
    {

        wp_register_script(
            'convertiser-widgets/payday-offers/css-element-queries',
            $this->plugin->getUrl('/assets/js/widgets/payday-offers/css-element-queries.min.js'),
            false,
            $this->plugin->getVersion()
        );

        wp_register_script(
            'convertiser-widgets/payday-offers/slick',
            $this->plugin->getUrl('/bower_components/slick-carousel/slick/slick.min.js'),
            array('jquery'),
            '1.6.0'
        );

        wp_register_script(
            'convertiser-widgets/payday-offers/main',
            $this->plugin->getUrl('/assets/js/widgets/payday-offers/main.min.js'),
            array('jquery', 'convertiser-widgets/payday-offers/slick'),
            $this->plugin->getVersion()
        );
    }

    /**
     * Returns default widget templates
     */
    public static function getDefaultTemplates()
    {
        $plugin = getPlugin();

        return array(
            'list'         => $plugin->getPath('templates/payday-offers/list.php'),
            'slider'       => $plugin->getPath('templates/payday-offers/slider.php'),
            'grid'         => $plugin->getPath('templates/payday-offers/grid.php'),
            'stacked'      => $plugin->getPath('templates/payday-offers/stacked.php'),
            'columns'      => $plugin->getPath('templates/payday-offers/columns.php'),
        );
    }


    /**
     * Payday widget: shortcode
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
            'widget_label'         => '',
            'type'                 => 'short_term',
            'only'                 => '',
            'exclude'              => '',
            'customer_age'         => '',
            'first_loan_fees'      => '',
            'blacklist_check'      => '',
            'income_proof'         => '',
            'loan_amount'          => '',
            'loan_period'          => '',
            'highlight'            => 3,
            'template'             => 'list',
            'show_loan_simulation' => 'yes',
            'show_loan_promotion'  => 'yes',
            'item_style'           => '',
            'featured_item_style'  => '',
            'promo_text_style'     => '',
            'cta_style'            => '',
            'cta_text'             => '',
            'limit'                => '',
            'ordering'             => 'rating',
            'fixed_order'          => '',
        ), $atts);

        $templates = apply_filters('convertiser_widgets_payday_offers_templates', static::getDefaultTemplates());

        if (empty($atts['show_loan_simulation'])) {
            $atts['show_loan_simulation'] = Settings::getOption('payday_offers_show_loan_simulation', 'yes');
        }

        // normalize values
        foreach ($atts as $key => $val) {
            // allow to modify shortcode attributes with filters
            $val = apply_filters('convertiser_widgets_payday_offers_shortcode_param_' . $key, trim($val));

            switch ($key) {
                case 'type':
                case 'only':
                case 'exclude':
                case 'fixed_order':
                    $atts[$key] = array();
                    if ($val) {
                        $atts[$key] = array_filter(array_map('sanitize_text_field', explode(',', $val)));
                    }
                    break;
                case 'first_loan_fees':
                case 'blacklist_check':
                case 'income_proof':
                case 'show_loan_simulation':
                case 'show_loan_promotion':
                    $atts[$key] = null;
                    if (in_array($val, array('yes', '1', 'true'), true)) {
                        $atts[$key] = true;
                    }
                    if (in_array($val, array('no', '0', 'false'), true)) {
                        $atts[$key] = false;
                    }
                    break;
                case 'customer_age':
                case 'loan_amount':
                case 'loan_period':
                case 'highlight':
                case 'limit':
                    $val        = (int)$val;
                    $val        = $val > 0 ? $val : null;
                    $atts[$key] = $val;
                    break;
                case 'template':
                    if (!array_key_exists($val, $templates) || !file_exists($templates[$val])) {
                        return sprintf(__('Template `%s` does not exist.', 'convertiser-widgets'), $val);
                    }

                    $atts[$key] = $val;
                    break;
                case 'widget_label':
                    $atts['widget_label'] = format_subid($atts['widget_label']);
                    break;
                case 'ordering':
                    $val        = in_array($val, array(
                        'rating',
                        'max_loan_period',
                        'max_loan_amount',
                        'first_loan_max_period',
                        'first_loan_max_amount',
                        'random',
                    ), true) ? $val : 'rating';
                    $atts[$key] = $val;
                    break;
                default:
                    $atts[$key] = clean($atts[$key]);
            }
        }

        $atts = apply_filters('convertiser_widgets_payday_offers_shortcode_atts', $atts);

        // Filter offers by type
        if(empty($atts['type'])) {
            $atts['type'] = 'short_term';
        }

        $offers_db = apply_filters('convertiser_widgets_payday_offers_shortcode_offers_db', static::getOffers(), $atts);
        $collection = collect($offers_db)->whereIn('loan_type', $atts['type']);

        // Include offers
        if ($atts['only']) {
            $collection = $collection->whereIn('domain', $atts['only']);
        }

        // Exclude offers
        if ($atts['exclude']) {
            $collection = $collection->whereNotIn('domain', $atts['exclude']);
        }

        // Age
        if ($atts['customer_age']) {
            $collection = $collection->where('min_customer_age', '<=', $atts['customer_age']);
            $collection = $collection->where('max_customer_age', '>=', $atts['customer_age']);
        }

        // First loan fees
        if ($atts['first_loan_fees'] !== null) {
            $collection = $collection->where('first_loan_fees', $atts['first_loan_fees']);
        }

        // Blacklist check
        if ($atts['blacklist_check'] !== null) {
            $collection = $collection->where('customer_blacklist_check', $atts['blacklist_check']);
        }

        // Income proof
        if ($atts['income_proof'] !== null) {
            $collection = $collection->where('customer_income_check', $atts['income_proof']);
        }

        // Loan amount
        if ($atts['loan_amount']) {
            $collection = $collection->where('min_loan_amount', '<=', $atts['loan_amount']);
            $collection = $collection->where('max_loan_amount', '>=', $atts['loan_amount']);
        }

        // Loan period
        if ($atts['loan_period']) {
            $collection = $collection->where('min_loan_period', '<=', $atts['loan_period']);
            $collection = $collection->where('max_loan_period', '>=', $atts['loan_period']);
        }

        // Sort results
        $atts['fixed_order'] = apply_filters(
            'convertiser_widgets_payday_offers_shortcode_fixed_order',
            $atts['fixed_order'],
            $collection->all(),
            $atts
        );

        if (count($atts['fixed_order']) > 0) {
            // Some offers with fixed position: sort just part of them and union!
            $fixed_items = $collection->whereIn(
                'domain',
                $atts['fixed_order']
            )->sortBy(function ($item, $key) use ($atts) {
                return array_search($item['domain'], $atts['fixed_order'], true);
            });

            $sortable_items = $collection->whereNotIn('domain', $atts['fixed_order']);

            if ($atts['ordering']) {
                if ($atts['ordering'] === 'random') {
                    $sortable_items = $sortable_items->shuffle();
                } else {
                    $sortable_items = $sortable_items->sortByDesc($atts['ordering']);
                }
            } else {
                $sortable_items = $sortable_items->sortByDesc('rating');
            }

            $collection = $fixed_items->union($sortable_items);
        } else {
            // No offer with fixed position: sort em all!
            if ($atts['ordering']) {
                if ($atts['ordering'] === 'random') {
                    $collection = $collection->shuffle();
                } else {
                    $collection = $collection->sortByDesc($atts['ordering']);
                }
            } else {
                $collection = $collection->sortByDesc('rating');
            }
        }

        // Limit number of offers
        if ($atts['limit']) {
            $collection = $collection->take($atts['limit']);
        }

        $offers = $collection->all();

        # No offers matching criteria (visible for admin ui only)
        if (!$offers) {
            if (is_admin()) {
                return __(
                    '<strong>Payday Offers Widget:</strong> There are no offers matching your filtering params.',
                    'convertiser-widgets'
                );
            }

            return '';
        }

        wp_enqueue_script('convertiser-widgets/payday-offers/css-element-queries');
        wp_enqueue_script('convertiser-widgets/payday-offers/slick');
        wp_enqueue_script('convertiser-widgets/payday-offers/main');

        // process label
        $label = 'Payday Offers: ' . ucwords($atts['template']);
        if ($atts['widget_label']) {
            $label .= ' [' . $atts['widget_label'] . ']';
        }
        $label = esc_attr($label);

        // Custom styles
        $darkColors = array(
            'turquoise',
            'green',
            'green-dark',
            'blue',
            'blue-dark',
            'purple',
            'wet-asphalt',
            'yellow',
            'orange',
            'red',
            'pink',
            'black',
        );

        $itemStyle = in_array($atts['item_style'], static::itemColors('item'), true)
            ? sprintf('cr--payday-%s__item--%s', $atts['template'], $atts['item_style'])
            : '';

        $noteStyle = '';
        $simulationStyle = '';
        if ($itemStyle !== '' && in_array($atts['item_style'], $darkColors, true)) {
            $itemStyle .= ' ' . sprintf('cr--payday-%s__item--lod', $atts['template']);
            $noteStyle = sprintf('cr--payday-%s__item__properties__note--lod', $atts['template']);
            $simulationStyle = sprintf('cr--payday-%s__item__loan-simulation--lod', $atts['template']);
        }

        $featuredItemStyle = in_array($atts['featured_item_style'], static::itemColors('featured_item'), true)
            ? sprintf('cr--payday-%s__item--featured-%s', $atts['template'], $atts['featured_item_style'])
            : '';

        $featuredNoteStyle = '';
        $featuredSimulationStyle = '';
        if ($featuredItemStyle !== '' && in_array($atts['featured_item_style'], $darkColors, true)) {
            $featuredItemStyle       .= ' ' . sprintf('cr--payday-%s__item--lod', $atts['template']);
            $featuredNoteStyle       = sprintf('cr--payday-%s__item__properties__note--lod', $atts['template']);
            $featuredSimulationStyle = sprintf('cr--payday-%s__item__loan-simulation--lod', $atts['template']);
        }

        $promoTextStyle = in_array($atts['promo_text_style'], static::itemColors('promo_text'), true)
            ? sprintf('cr--payday-%s__item__promo--%s', $atts['template'], $atts['promo_text_style'])
            : '';

        $ctaStyle = in_array($atts['cta_style'], static::itemColors('cta'), true)
            ? sprintf('cr--payday-%s__item__cta__button--%s', $atts['template'], $atts['cta_style'])
            : '';

        $render = function () use (
            $offers,
            $atts,
            $templates,
            $label,
            $itemStyle,
            $featuredItemStyle,
            $promoTextStyle,
            $ctaStyle,
            $noteStyle,
            $simulationStyle,
            $featuredNoteStyle,
            $featuredSimulationStyle
        ) {
            ob_start();

            /* Print styles */
            $print = apply_filters('convertiser_widgets_payday_offers_print_default_styles', true);
            if ($print === true) {
                $this->getShortcodeAssets('[convertiser_payday_offers]', true);
            }

            $offers = apply_filters('convertiser_widgets_payday_offers_render_offers', $offers);
            $templates = apply_filters('convertiser_widgets_payday_offers_render_templates', $templates);
            $label = apply_filters('convertiser_widgets_payday_offers_render_label', $label);
            $itemStyle = apply_filters('convertiser_widgets_payday_offers_render_item_style', $itemStyle);
            $featuredItemStyle = apply_filters('convertiser_widgets_payday_offers_render_featured_item_style', $featuredItemStyle);
            $promoTextStyle = apply_filters('convertiser_widgets_payday_offers_render_promo_text_style', $promoTextStyle);
            $ctaStyle = apply_filters('convertiser_widgets_payday_offers_render_cta_style', $ctaStyle);
            $noteStyle = apply_filters('convertiser_widgets_payday_offers_render_note_style', $noteStyle);
            $simulationStyle = apply_filters('convertiser_widgets_payday_offers_render_simulation_style', $simulationStyle);
            $featuredNoteStyle = apply_filters('convertiser_widgets_payday_offers_render_featured_note_style', $featuredNoteStyle);
            $featuredSimulationStyle = apply_filters('convertiser_widgets_payday_offers_render_featured_simulation_style', $featuredSimulationStyle);

            do_action(
                'convertiser_widgets_payday_offers_before_render',
                $offers,
                $atts,
                $templates,
                $label,
                $itemStyle,
                $featuredItemStyle,
                $promoTextStyle,
                $ctaStyle,
                $noteStyle,
                $simulationStyle,
                $featuredNoteStyle,
                $featuredSimulationStyle
            );

            /** @noinspection PhpIncludeInspection */
            include $templates[$atts['template']];

            do_action(
                'convertiser_widgets_payday_offers_after_render',
                $offers,
                $atts,
                $templates,
                $label,
                $itemStyle,
                $featuredItemStyle,
                $promoTextStyle,
                $ctaStyle,
                $noteStyle,
                $simulationStyle,
                $featuredNoteStyle,
                $featuredSimulationStyle
            );

            return ob_get_clean();
        };

        return $render();
    }

    /**
     * Returns styles and scripts required by shortcode preview
     *
     * @param $shortcode
     * @param bool $styles_only
     */
    public function getShortcodeAssets($shortcode, $styles_only=false)
    {
        if (strpos($shortcode, '[convertiser_payday_offers') !== 0) {
            echo '';
            return;
        }

        $scripts = array(
            'https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js',
            $this->plugin->getUrl('/assets/js/widgets/payday-offers/css-element-queries.min.js'),
            $this->plugin->getUrl('/bower_components/slick-carousel/slick/slick.min.js'),
            $this->plugin->getUrl('/assets/js/widgets/payday-offers/main.min.js'),
        );

        $styles = array(
            'https://fonts.googleapis.com/css?family=Roboto:300,400,700,900|Open+Sans:400,700&subset=latin-ext',
            $this->plugin->getUrl('/assets/css/payday-offers.css'),
            $this->plugin->getUrl('/assets/css/slick.css'),
        );

        $out = array();
        if($styles_only === false) {
            foreach ($scripts as $script) {
                $out[] = sprintf('<script src="%s"></script>', $script);
            }
        }

        foreach ($styles as $style) {
            $out[] = sprintf('<link media="all" rel="stylesheet" href="%s" />', $style);
        }

        echo implode('', $out);
    }

    /**
     * Available styles for single offer item
     * @return array
     */
    public static function itemColors($type)
    {
        $colors = array();
        switch ($type) {
            case 'item':
                $colors = array(
                    'plain',
                    'light-green',
                    'light-blue',
                    'light-purple',
                    'light-gray',
                    'light-yellow',
                    'light-red',
                    'turquoise',
                    'green',
                    'green-dark',
                    'blue',
                    'blue-dark',
                    'purple',
                    'wet-asphalt',
                    'yellow',
                    'orange',
                    'red',
                    'pink',
                    'black',
                    'white',
                );
                break;
            case 'featured_item':
                $colors = array(
                    'light-green',
                    'light-blue',
                    'light-purple',
                    'light-gray',
                    'light-yellow',
                    'light-red',
                    'green',
                    'blue',
                    'purple',
                    'gray',
                    'yellow',
                    'red'
                );
                break;
            case 'promo_text':
                $colors = array(
                    'turquoise',
                    'green',
                    'green-dark',
                    'blue',
                    'blue-dark',
                    'purple',
                    'wet-asphalt',
                    'yellow',
                    'orange',
                    'red',
                    'pink',
                    'black',
                    'white',
                );
                break;
            case 'cta':
                $colors = array(
                    'turquoise',
                    'green',
                    'green-dark',
                    'blue',
                    'blue-dark',
                    'purple',
                    'wet-asphalt',
                    'yellow',
                    'orange',
                    'red',
                    'pink',
                    'black',
                );
        }

        return $colors;
    }

    /**
     * Returns redirect prefix
     * @return string
     */
    public static function getRedirectBase()
    {
        $permalinks = get_option(static::$redirect_opt_key);
        $cloak_base = 'go';
        if (isset($permalinks['payday_offers_redirect_base']) && !empty($permalinks['payday_offers_redirect_base'])) {
            $cloak_base = $permalinks['payday_offers_redirect_base'];
        }

        return $cloak_base;
    }

    /**
     * Setup affiliate links cloaking
     */
    public function setupLinksCloaking()
    {
        $cloak_base = static::getRedirectBase();

        add_filter(
            'query_vars',

            /**
             * Register a new query var.
             */
            function ($vars) use ($cloak_base) {
                $vars[] = $cloak_base;

                return $vars;
            }
        );

        add_filter(
            'rewrite_rules_array',

            /**
             * Add the new rewrite rule to existing ones.
             */
            function ($rules) use ($cloak_base) {
                $new_rules = array($cloak_base . '/([^/]+)/?' => 'index.php?' . $cloak_base . '=$matches[1]');
                return array_merge($new_rules, $rules);
            }
        );

        add_filter(
            'robots_txt',

            /**
             * Add "permalink" path to robots.txt file.
             */
            function ($output, $public) use ($cloak_base) {
                if (get_option('permalink_structure')) {
                    $home_url = parse_url(home_url());
                    $path     = (!empty($home_url['path'])) ? $home_url['path'] : '';
                    $text     = "Disallow: $path/" . $cloak_base . "/\n";
                    $text     = apply_filters('convertiser_widgets_payday_offers_robots_txt', $text);
                    $output .= $text;
                }

                return $output;
            },
            10,
            2
        );

        add_action(
            'template_redirect',

            /**
             * Redirect the user to external link.
             */
            function () use ($cloak_base) {

                global $wp_query;

                $slug = get_query_var($cloak_base);
                if ($slug) {
                    $offer = collect(static::getOffers())->where('domain', strtolower($slug))->first();
                    if (null !== $offer) {
                        $url = apply_filters('convertiser_widgets_payday_offers_redirect_url', $offer['tracking_link']);
                        do_action('convertiser_widgets_payday_offers_redirect', $url, $offer);
                        wp_redirect($url);
                    } else {
                        $wp_query->set_404();
                        status_header(404);
                    }
                }
            },
            1
        );

        $permalink_input = function () use ($cloak_base) {
            printf(
                '<input name="%s" type="text" class="regular-text code" value="%s" placeholder="%s"><code>%s</code>',
                'convertiser_widgets_payday_offers_redirect_base',
                $cloak_base,
                _x('redirect', 'slug', 'convertiser-widgets'),
                '/%offer_domain%/'
            );
        };

        add_action(
            'admin_init',

            /**
             * UI for redirect slug
             */
            function () use ($permalink_input) {
                add_settings_field(
                    'convertiser_widgets_payday_offers_redirect_slug',
                    __('Payday Offer Link Base', 'convertiser-widgets'),
                    $permalink_input,
                    'permalink',
                    'optional'
                );
            }
        );

        add_action(
            'admin_init',

            /**
             * Handle UI redirect slug updates
             */
            function () {
                if (!is_admin()) {
                    return;
                }

                if (isset($_POST['convertiser_widgets_payday_offers_redirect_base'])) {
                    $affiliate_base = clean($_POST['convertiser_widgets_payday_offers_redirect_base']);
                    $permalinks     = get_option(static::$redirect_opt_key);
                    if (!$permalinks) {
                        $permalinks = array();
                    }
                    $permalinks['payday_offers_redirect_base'] = untrailingslashit($affiliate_base);
                    update_option(static::$redirect_opt_key, $permalinks);
                }
            }
        );
    }

    /**
     * Capture tracking parameters such as tags, keyword, subid etc.
     * This data will be reported to Convertiser
     */
    public static function captureTrackingParams()
    {
        // disable for admin section
        if (is_admin()) {
            return;
        }

        // disable for outside redirect
        if (get_query_var(static::getRedirectBase())) {
            return;
        }

        $capture = function ($token) {
            return trim((string)filter_input(INPUT_GET, $token, FILTER_SANITIZE_STRING));
        };

        $current = filter_empty(static::retrieveTrackingParams());
        $data = filter_empty(
            array(
                'keyword' => $capture('keyword'),
                'sid'     => format_subid($capture('sid')),
                'tag1'    => $capture('tag1'),
                'tag2'    => $capture('tag2'),
                'tag3'    => $capture('tag3'),
                'tag4'    => $capture('tag4'),
                'tag5'    => $capture('tag5'),
            )
        );

        $data = apply_filters('convertiser_widgets_payday_offers_capture_tracking_params', array_merge($current, $data));
        $data = http_build_query($data);

        $ttl = time() + (30 * DAY_IN_SECONDS);
        setcookie(static::$tracking_cookie, $data, $ttl, COOKIEPATH, COOKIE_DOMAIN, false, true);
    }

    /**
     * Returns tracking params from cookie
     */
    public static function retrieveTrackingParams()
    {
        if (isset($_COOKIE[static::$tracking_cookie])) {
            parse_str($_COOKIE[static::$tracking_cookie], $out);
            return apply_filters('convertiser_widgets_payday_offers_retrieve_tracking_params', $out);
        }

        return array();
    }

    /**
     * Adds tracking params into redirect url
     *
     * @param $url
     * @return string
     */
    public static function applyTrackingParams($url)
    {
        $url_parts = parse_url($url);

        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $params);
        } else {
            $params = array();
        }

        $allowed_request_params = ['keyword', 'sid', 'tag1', 'tag2', 'tag3', 'tag4', 'tag5'];
        $request                = array_filter(
            $_GET,
            function ($key) use ($allowed_request_params) {
                return in_array($key, $allowed_request_params, true);
            },
            ARRAY_FILTER_USE_KEY
        );

        $params = array_merge(
            filter_empty($params),
            filter_empty($request),
            filter_empty(static::retrieveTrackingParams())
        );
        $params = apply_filters('convertiser_widgets_payday_offers_apply_tracking_params', $params);
        $url    = http_build_url($url, array('query' => http_build_query($params)));

        return $url;
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

        $select2options = function ($input, $source = 'keys') {
            $out = array();
            $out[] = array('id' => '', 'text' => '');

            foreach ($input as $k => $v) {
                $val = $source === 'keys' ? $k : $v;
                $out[] = array(
                    'id'   => $val,
                    'text' => ucwords(str_replace(array('-', '_'), ' ', $val))
                );
            }

            return $out;
        };

        $offers = collect(array(
            array('id' => '', 'text' => '')
        ));

        foreach (static::getOffers() as $o) {
            $domain = strtolower($o['domain']);
            if ($offers->whereStrict('id', $domain)->count() === 0) {
                $offers->push(
                    array(
                        'id'   => $domain,
                        'text' => $o['title'],
                    )
                );
            }
        }

        $offers = $offers->sortBy('id')->values()->toArray();

        $templates       = apply_filters(
            'convertiser_widgets_payday_offers_templates',
            static::getDefaultTemplates()
        );
        $named_templates = $select2options($templates);


        $yesno = array(
            array('id' => '', 'text' => ''),
            array('id' => 'yes', 'text' => __('Yes', 'convertiser-widgets')),
            array('id' => 'no', 'text' => __('No', 'convertiser-widgets')),
        );

        // styles
        $itemStyles = $select2options(static::itemColors('item'), 'values');
        $featuredItemStyles = $select2options(static::itemColors('featured_item'), 'values');
        $promoTextStyles = $select2options(static::itemColors('promo_text'), 'values');
        $ctaStyles = $select2options(static::itemColors('cta'), 'values');

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
                'label'       => esc_html__('Widget Label', 'convertiser-widgets'),
                'description' => esc_html__(
                    'Optional widget label that would be reported to Google Analytics. 
                    Requires GA integration to be enabled.',
                    'convertiser-widgets'
                ),
                'attr'        => 'widget_label',
                'type'        => 'text',
                'value'       => '',
            ),

            array(
                'label'    => esc_html__('Loan Type', 'convertiser-widgets'),
                'attr'     => 'type',
                'type'     => 'local_select2',
                'multiple' => true,
                'options'  => array(
                    array(
                        'id'   => '',
                        'text' => ''
                    ),
                    array(
                        'id'   => 'short_term',
                        'text' => esc_html__('Short Term Loans', 'convertiser-widgets')
                    ),
                    array(
                        'id'   => 'long_term',
                        'text' => esc_html__('Long Term Loans', 'convertiser-widgets')
                    ),
                ),
                'value'    => 'short_term',
                'meta'     => array(
                    'data-placeholder' => __('Select option', 'convertiser-widgets'),
                ),
            ),

            array(
                'label'       => esc_html__('Loan Amount', 'convertiser-widgets'),
                'description' => esc_html__('Filter offers that match given loan amount', 'convertiser-widgets'),
                'attr'        => 'loan_amount',
                'type'        => 'number',
                'value'       => '',
                'meta'        => array('min' => 100),
            ),

            array(
                'label'       => esc_html__('Loan Period (days)', 'convertiser-widgets'),
                'description' => esc_html__('Filter offers that match given loan period', 'convertiser-widgets'),
                'attr'        => 'loan_period',
                'type'        => 'number',
                'value'       => '',
                'meta'        => array('min' => 1),
            ),

            array(
                'label'       => esc_html__('First loan free of charges', 'convertiser-widgets'),
                'description' => esc_html__('Filter offers by fees policy for new customers.', 'convertiser-widgets'),
                'attr'        => 'first_loan_fees',
                'type'        => 'local_select2',
                'options'     => $yesno,
                'meta'        => array(
                    'data-allow-clear' => 1,
                    'data-placeholder' => __('Select option', 'convertiser-widgets')
                ),
            ),

            array(
                'label'       => esc_html__('Blacklist Check', 'convertiser-widgets'),
                'description' => esc_html__('Filter offers by blacklist check policy.', 'convertiser-widgets'),
                'attr'        => 'blacklist_check',
                'type'        => 'local_select2',
                'options'     => $yesno,
                'meta'        => array(
                    'data-allow-clear' => 1,
                    'data-placeholder' => __('Select option', 'convertiser-widgets')
                ),
            ),

            array(
                'label'       => esc_html__('Income Checks', 'convertiser-widgets'),
                'description' => esc_html__(
                    'Filter offers by income/work documents check policy.',
                    'convertiser-widgets'
                ),
                'attr'        => 'income_proof',
                'type'        => 'local_select2',
                'options'     => $yesno,
                'meta'        => array(
                    'data-allow-clear' => 1,
                    'data-placeholder' => __('Select option', 'convertiser-widgets')
                ),
            ),

            array(
                'label'       => esc_html__('Customer Age', 'convertiser-widgets'),
                'description' => esc_html__('Filter offers by customer age.', 'convertiser-widgets'),
                'attr'        => 'customer_age',
                'type'        => 'number',
                'value'       => '',
                'meta'        => array('min' => 18),
            ),

            array(
                'label'       => esc_html__('Include offers', 'convertiser-widgets'),
                'description' => esc_html__('Limit offers to selected ones.', 'convertiser-widgets'),
                'attr'        => 'only',
                'type'        => 'local_select2',
                'options'     => $offers,
                'multiple'    => true,
                'meta'        => array(
                    'data-placeholder' => __('Select offers', 'convertiser-widgets'),
                ),
            ),

            array(
                'label'       => esc_html__('Exclude offers', 'convertiser-widgets'),
                'description' => esc_html__('Exclude selected offers.', 'convertiser-widgets'),
                'attr'        => 'exclude',
                'type'        => 'local_select2',
                'options'     => $offers,
                'multiple'    => true,
                'meta'        => array(
                    'data-placeholder' => __('Select offers', 'convertiser-widgets'),
                ),
            ),

            array(
                'label'       => esc_html__('Highlight', 'convertiser-widgets'),
                'description' => esc_html__('Mark given number of offers as recommended', 'convertiser-widgets'),
                'attr'        => 'highlight',
                'type'        => 'number',
                'value'       => 3,
                'meta'        => array('min' => 0),
            ),

            array(
                'label'   => esc_html__('Template', 'convertiser-widgets'),
                'attr'    => 'template',
                'type'    => 'local_select2',
                'options' => $named_templates,
                'value'   => count($named_templates) > 1 ? $named_templates[1]['id'] : '',
                'meta'        => array(
                    'data-placeholder' => __('Select option', 'convertiser-widgets'),
                ),
            ),

            array(
                'label'       => esc_html__('Show loan simulation', 'convertiser-widgets'),
                'description' => esc_html__(
                    'Required by Google AdWords rules. Not all templates support this option.',
                    'convertiser-widgets'
                ),
                'attr'        => 'show_loan_simulation',
                'type'        => 'local_select2',
                'options'     => $yesno,
                'meta'        => array(
                    'data-allow-clear' => 0,
                    'data-placeholder' => __('Select option', 'convertiser-widgets')
                ),
            ),

            array(
                'label'   => esc_html__('Item Style', 'convertiser-widgets'),
                'attr'    => 'item_style',
                'type'    => 'local_select2',
                'options' => $itemStyles,
                'value'   => '',
                'meta'        => array(
                    'data-allow-clear' => 1,
                    'data-placeholder' => __('Select option', 'convertiser-widgets'),
                ),
            ),

            array(
                'label'   => esc_html__('Featured Item Style', 'convertiser-widgets'),
                'attr'    => 'featured_item_style',
                'type'    => 'local_select2',
                'options' => $featuredItemStyles,
                'value'   => '',
                'meta'        => array(
                    'data-allow-clear' => 1,
                    'data-placeholder' => __('Select option', 'convertiser-widgets'),
                ),
            ),

            array(
                'label'       => esc_html__('Show promo text', 'convertiser-widgets'),
                'description' => esc_html__(
                    'Offers may contain optional promo text.',
                    'convertiser-widgets'
                ),
                'attr'        => 'show_loan_promotion',
                'type'        => 'local_select2',
                'options'     => $yesno,
                'meta'        => array(
                    'data-allow-clear' => 0,
                    'data-placeholder' => __('Select option', 'convertiser-widgets')
                ),
            ),

            array(
                'label'   => esc_html__('Promo Text Style', 'convertiser-widgets'),
                'attr'    => 'promo_text_style',
                'type'    => 'local_select2',
                'options' => $promoTextStyles,
                'value'   => '',
                'meta'        => array(
                    'data-allow-clear' => 1,
                    'data-placeholder' => __('Select option', 'convertiser-widgets'),
                ),
            ),

            array(
                'label'   => esc_html__('CTA Button Style', 'convertiser-widgets'),
                'attr'    => 'cta_style',
                'type'    => 'local_select2',
                'options' => $ctaStyles,
                'value'   => '',
                'meta'        => array(
                    'data-allow-clear' => 1,
                    'data-placeholder' => __('Select option', 'convertiser-widgets'),
                ),
            ),

            array(
                'label'       => esc_html__('CTA Button Text', 'convertiser-widgets'),
                'description' => esc_html__(
                    'Specifies call to action button text. Supports replacements: {offer} -> offer title, 
                    {domain} -> offer domain, {br} -> line break.',
                    'convertiser-widgets'
                ),
                'attr'        => 'cta_text',
                'type'        => 'text',
                'value'       => '',
            ),

            array(
                'label'       => esc_html__('Limit', 'convertiser-widgets'),
                'description' => esc_html__('Limit number of offers', 'convertiser-widgets'),
                'attr'        => 'limit',
                'type'        => 'number',
                'value'       => '',
                'meta'        => array('min' => 1),
            ),

            array(
                'label'   => esc_html__('Ordering', 'convertiser-widgets'),
                'attr'    => 'ordering',
                'type'    => 'local_select2',
                'options' => array(
                    array(
                        'id'   => '',
                        'text' => ''
                    ),
                    array(
                        'id'   => 'rating',
                        'text' => esc_html__('Rating', 'convertiser-widgets')
                    ),
                    array(
                        'id'   => 'max_loan_period',
                        'text' => esc_html__('Max Loan Period', 'convertiser-widgets')
                    ),
                    array(
                        'id'   => 'max_loan_amount',
                        'text' => esc_html__('Max Loan Amount', 'convertiser-widgets')
                    ),
                    array(
                        'id'   => 'first_loan_max_period',
                        'text' => esc_html__('First Loan Max Period', 'convertiser-widgets')
                    ),
                    array(
                        'id'   => 'first_loan_max_amount',
                        'text' => esc_html__('First Loan Max Amount', 'convertiser-widgets')
                    ),
                    array(
                        'id'   => 'random',
                        'text' => esc_html__('Random', 'convertiser-widgets')
                    ),
                ),
                'value'   => 'rating',
                'meta'        => array(
                    'data-allow-clear' => 1,
                    'data-placeholder' => __('Select option', 'convertiser-widgets'),
                ),
            ),
            array(
                'label'       => esc_html__('Fixed Order', 'convertiser-widgets'),
                'description' => esc_html__(
                    'Defines one or more offers that should be pinned to the top of the list in a specific order.',
                    'convertiser-widgets'
                ),
                'attr'        => 'fixed_order',
                'type'        => 'local_select2',
                'options'     => $offers,
                'multiple'    => true,
                'meta'        => array(
                    'data-placeholder' => __('Select offers', 'convertiser-widgets'),
                ),
            ),
        );
        /*
         * Define the Shortcode UI arguments.
         */
        $shortcode_ui_args = array(
            /*
             * How the shortcode should be labeled in the UI. Required argument.
             */
            'label'         => esc_html__('Payday Offers', 'convertiser-widgets'),
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

        shortcode_ui_register_for_shortcode('convertiser_payday_offers', $shortcode_ui_args);
    }
}


/**
 * Returns formatted loan `min - max amount`
 *
 * @param $minLoan
 * @param $maxLoan
 * @param string $currency
 *
 * @return string
 */
function get_formatted_loan_amount($minLoan, $maxLoan, $currency = 'zł')
{
    return sprintf('%s - %s %s', $minLoan, $maxLoan, $currency);
}

/**
 * Format bool/int to text: true/1 -> Yes/No
 *
 * @param bool|int $val
 * @param bool $icon
 *
 * @return string
 */
function format_bool($val)
{
    return (bool)((int)$val) ? 'Tak' : 'Nie';
}

/**
 * Shortcut to format days -> months
 *
 * @param $days
 * @param int $minDaysEdge Minimum days to prevent conversion days to months
 *
 * @return array (int, unit_code, unit_name)
 */
function format_days($days, $minDaysEdge = 60)
{
    if ($days % 30 === 0 && $days > $minDaysEdge) {
        // months
        $days = array($days / 30, 'm', 'mies.');
    } else {
        // days
        $unit = $days > 1 ? 'dni' : 'dzień';
        $days = array($days, 'd', $unit);
    }

    return $days;
}

/**
 * Returns formatted loan length `15days - 23months`
 *
 * @param $minDays
 * @param $maxDays
 *
 * @return string
 */
function get_formatted_loan_length($minDays, $maxDays)
{
    $minDays = format_days($minDays);
    $maxDays = format_days($maxDays);

    // if both units are the same, simplify rendering
    if ($minDays[1] === $maxDays[1]) {
        return sprintf('%s - %s %s', $minDays[0], $maxDays[0], $maxDays[2]);
    }

    return sprintf('%s %s - %s %s', $minDays[0], $minDays[2], $maxDays[0], $maxDays[2]);
}


/**
 * Checks whether Universal GA is enabled.
 * @return bool
 */
function ga_should_track()
{
    return Settings::getOption('payday_offers_google_analytics_enabled') === 'yes';
}


/**
 * Google Analytics: Track onClick event
 *
 * ga('send', 'event', [eventCategory], [eventAction], [eventLabel], [eventValue], [fieldsObject]);
 * @see: https://developers.google.com/analytics/devguides/collection/analyticsjs/events
 *
 *
 * @param $category
 * @param $action
 * @param $label
 * @param $value
 *
 * @return string
 */
function get_analytics_onclick_event($category, $action, $label, $value = null)
{
    if (!ga_should_track()) {
        return '';
    }

    if (is_numeric($value)) {
        $onClick = sprintf(
            "if(typeof(ga) === 'function'){ga(function(t){ ga(ga.getAll().length > 0 ? ga.getAll()[0].get('name') + '.send' : 'send', 'event', '%s', '%s', '%s', %s); })}",
            esc_js($category),
            esc_js($action),
            esc_js($label),
            (int)$value
        );
    } else {
        $onClick = sprintf(
            "if(typeof(ga) === 'function'){ga(function(t){ ga(ga.getAll().length > 0 ? ga.getAll()[0].get('name') + '.send' : 'send', 'event', '%s', '%s', '%s'); })}",
            esc_js($category),
            esc_js($action),
            esc_js($label)
        );
    }

    $onClick = sprintf('onclick="%s"', $onClick);

    return $onClick;
}

/**
 * Google Analytcis: Track non interactive actions
 *
 * @param $category
 * @param $action
 * @param $label
 *
 * @return string
 */
function get_analytics_non_interactive_event($category, $action, $label)
{
    if (!ga_should_track()) {
        return '';
    }

    $fn = sprintf(
        "if(typeof(ga) === 'function'){ga(function(t){ ga(ga.getAll().length > 0 ? ga.getAll()[0].get('name') + '.send' : 'send', 'event', '%s', '%s', '%s', {nonInteraction: true}) })}",
        esc_js($category),
        esc_js($action),
        esc_js($label)
    );
    $fn = sprintf('<script>%s</script>', $fn);

    return $fn;
}

/**
 * @param string $default default CTA text
 * @param string $offerTitle offer title
 * @param string $offerDomain domain (foo.com)
 * @param string $optional optional CTA text (with templates)
 *
 * @return string
 */
function get_cta_text($default, $offerTitle, $offerDomain, $optional = '')
{
    $optional = trim($optional);

    if ($optional) {
        return str_replace(
            array('{offer}', '{domain}', '{br}'),
            array(esc_html($offerTitle), esc_html($offerDomain), '<br>'),
            esc_html($optional)
        );
    }

    return $default;
}

/**
 * Generate offer redirect url
 *
 * @param array $offer
 * @param string $sid
 *
 * @return string
 */
function get_tracking_url(array $offer, $sid = '')
{
    $cloak_base = PaydayOffersWidget::getRedirectBase();
    $permalink = get_option('permalink_structure');

    if ($permalink !== '') {
        $path = sprintf('/%s/%s/', $cloak_base, strtolower($offer['domain']));
        if ($sid) {
            $path .= '?sid=' . format_subid($sid);
        }
    } else {
        $path = sprintf('/index.php?%s=%s', $cloak_base, strtolower($offer['domain']));
        if ($sid) {
            $path .= '&sid=' . format_subid($sid);
        }
    }

    return wp_make_link_relative(get_home_url(null, $path));
}

/**
 * Generate inline css style for logos
 * @param $logoUrl
 *
 * @return string
 */
function get_logo_css($logoUrl)
{
    $logoUrl = esc_url($logoUrl);

    return "background: transparent url('{$logoUrl}') no-repeat center center !important; 
    background-size: contain !important;";
}
