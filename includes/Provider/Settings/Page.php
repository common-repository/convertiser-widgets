<?php
/**
 * Abstract Settings Page/Tab
 */
namespace Convertiser\Widgets\Provider\Settings;

use Convertiser\Widgets\Provider\Settings;

abstract class Page
{
    protected $id = '';
    protected $label = '';

    /**
     * Constructor
     */
    public function registerHooks()
    {
        add_filter('convertiser_widgets_settings_tabs_array', array($this, 'addSettingsPage'), 20);
        add_action('convertiser_widgets_sections_' . $this->id, array($this, 'outputSections'));
        add_action('convertiser_widgets_settings_' . $this->id, array($this, 'output'));
    }

    /**
     * Getter for $id
     *
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Getter for $label
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Add this page to settings
     *
     * @param $pages
     *
     * @return array
     */
    public function addSettingsPage($pages)
    {
        $pages[$this->id] = $this->label;

        return $pages;
    }

    /**
     * Get settings array
     *
     * @return array
     */
    public function getSettings()
    {
        return apply_filters('convertiser_widgets_get_settings_' . $this->id, array());
    }

    /**
     * Get sections
     *
     * @return array
     */
    public function getSections()
    {
        return apply_filters('convertiser_widgets_get_sections_' . $this->id, array());
    }

    /**
     * Output sections
     */
    public function outputSections()
    {
        global $current_section;

        $sections = $this->getSections();

        if (empty($sections)) {
            return;
        }

        echo '<ul class="subsubsub">';

        $array_keys = array_keys($sections);

        foreach ($sections as $id => $label) {
            echo '<li><a href="' . admin_url('options-general.php?page=convertiser-widgets-settings&tab=' . $this->id . '&section=' . sanitize_title($id)) . '" class="' . ($current_section === $id ? 'current' : '') . '">' . $label . '</a> ' . (end($array_keys) === $id ? '' : '|') . ' </li>';
        }

        echo '</ul><br class="clear" />';
    }

    /**
     * Optional settings tab header
     */
    public function outputHeader()
    {
        return '';
    }

    /**
     * Output the settings
     */
    public function output()
    {
        $settings = $this->getSettings();
        echo $this->outputHeader();
        Settings::outputFields($settings);
    }
}
