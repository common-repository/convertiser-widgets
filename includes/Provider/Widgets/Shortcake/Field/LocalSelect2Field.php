<?php
/**
 * Shortcake Field: Comparison Widget Preview
 */

namespace Convertiser\Widgets\Provider\Widgets\Shortcake\Field;

use function Convertiser\Widgets\getPlugin;
use Shortcode_UI;

class LocalSelect2Field
{
    private static $instance;

    // Field Settings.
    private $fields = array(
        'local_select2' => array(
            'template' => 'shortcode-ui-field-local-select2',
            'view'     => 'editAttributeFieldLocalSelect2',
        ),
    );

    /**
     * Setup the instance.
     *
     * @return LocalSelect2Field
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

        wp_enqueue_script(
            'local-select2-sui',
            getPlugin()->getUrl('/assets/js/shortcake/local-select2.min.js'),
            array('jquery', 'shortcode-ui'),
            getPlugin()->getVersion()
        );
    }

    /**
     * Output styles and templates used by the select field.
     */
    public function actionShortcodeUILoadedEditor()
    {
        ?>

        <script type="text/html" id="tmpl-shortcode-ui-field-local-select2">
            <div class="field-block shortcode-ui-field-local-select2 shortcode-ui-attribute-{{ data.attr }}">
                <label for="{{ data.id }}">{{{ data.label }}}</label>
                <select name="{{ data.attr }}" id="{{ data.id }}" class="shortcode-ui-local-select2" {{{ data.meta }}}>
                </select>
                <# if ( typeof data.description == 'string' && data.description.length ) { #>
                <p class="description">{{{ data.description }}}</p>
                <# } #>
            </div>
        </script>

        <?php

    }
}
