<?php
/**
 * Common plugin functionality.
 */
namespace Convertiser\Widgets;

/**
 * Abstract plugin class.
 */
abstract class AbstractPlugin
{
    /**
     * Plugin version
     *
     * @var string
     */
    protected $version;

    /**
     * Plugin basename.
     *
     * Ex: plugin-name/plugin-name.php
     *
     * @var string
     */
    protected $basename;

    /**
     * Absolute path to the main plugin directory.
     *
     * @var string
     */
    protected $directory;

    /**
     * Absolute path to the main plugin file.
     *
     * @var string
     */
    protected $file;

    /**
     * Plugin identifier.
     *
     * @var string
     */
    protected $slug;

    /**
     * URL to the main plugin directory.
     *
     * @var string
     */
    protected $url;


    /**
     * Retrieve the relative path from the main plugin directory.
     *
     * @return string
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * Set the plugin basename.
     *
     * @param  string $basename Relative path from the main plugin directory.
     *
     * @return $this
     */
    public function setBasename($basename)
    {
        $this->basename = $basename;

        return $this;
    }

    /**
     * Retrieve plugin version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set the plugin version.
     *
     * @param  string $version
     *
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Retrieve the plugin directory.
     *
     * @return string
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set the plugin's directory.
     *
     * @param  string $directory Absolute path to the main plugin directory.
     *
     * @return $this
     */
    public function setDirectory($directory)
    {
        $this->directory = rtrim($directory, '/') . '/';

        return $this;
    }

    /**
     * Retrieve the path to a file in the plugin.
     *
     * @param  string $path Optional. Path relative to the plugin root.
     *
     * @return string
     */
    public function getPath($path = '')
    {
        return $this->directory . ltrim($path, '/');
    }

    /**
     * Retrieve the absolute path for the main plugin file.
     *
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Set the path to the main plugin file.
     *
     * @param  string $file Absolute path to the main plugin file.
     *
     * @return $this
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * Retrieve the plugin identifier.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set the plugin identifier.
     *
     * @param  string $slug Plugin identifier.
     *
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Retrieve the URL for a file in the plugin.
     *
     * @param  string $path Optional. Path relative to the plugin root.
     *
     * @return string
     */
    public function getUrl($path = '')
    {
        return $this->url . ltrim($path, '/');
    }

    /**
     * Set the URL for plugin directory root.
     *
     * @param  string $url URL to the root of the plugin directory.
     *
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = rtrim($url, '/') . '/';

        return $this;
    }

    /**
     * Register a hook provider.
     *
     * @param  AbstractProvider $provider Hook provider.
     *
     * @return $this
     */
    public function registerHooks($provider)
    {
        if (method_exists($provider, 'setPlugin')) {
            $provider->setPlugin($this);
        }

        $provider->registerHooks();

        return $this;
    }

    /**
     * Get AJAX URL
     * @return string
     */
    public function ajaxURL()
    {
        return admin_url('admin-ajax.php', 'relative');
    }

    /**
     * What type of request is this?
     * string $type ajax, frontend or admin
     *
     * @param string $type
     *
     * @return bool
     */
    public function requestTypeIs($type)
    {
        switch ($type) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined('DOING_AJAX');
            case 'cron':
                return defined('DOING_CRON');
            case 'frontend':
                return (! is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
        }

        return null;
    }
}
