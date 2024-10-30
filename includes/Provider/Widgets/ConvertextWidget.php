<?php
/**
 * Convertiser InText ads: ConvertextWidget
 */
namespace Convertiser\Widgets\Provider\Widgets;

use Convertiser\Widgets\AbstractProvider;
use Convertiser\Widgets\Provider\Settings;

class ConvertextWidget extends AbstractProvider
{
    public function registerHooks()
    {
        add_action('wp_head', array($this, 'output'));
    }

    public function isEnabled()
    {
        return Settings::getOption('convertext_enabled') === 'yes';
    }

    public function output()
    {
        if (! $this->isEnabled() || ! $this->plugin->isConfigured()) {
            return '';
        }

        $code = <<<HTML
<script type="text/javascript">// <![CDATA[
window.ctxtconfig = {"debug":false,"theme":"blue","frequency":%frequency%,"key":%key%,"widget":false,"positive_cls":%positive_cls%,"negative_cls":%negative_cls%,"ignore_domains":%ignore_domains%,"features":%features%};
(function() {
    if (document.getElementById('ctxt-script')) {return;}
    var ctxt = document.createElement('script');
    ctxt.type = 'text/javascript';
    ctxt.async = true;
    ctxt.charset = 'utf-8';
    ctxt.id = 'ctxt-script';
    ctxt.src = '//converti.se/convertext.js';
    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(ctxt, s);
})();
// ]]></script>
HTML;

        $features       = array();
        $known_features = array(
            'modlinks' => 'convertext_modlinks',
            'modwords' => 'convertext_modwords',
        );

        foreach ($known_features as $module => $setting) {
            if (Settings::getOption($setting) === 'yes') {
                $features[] = $module;
            }
        }

        // no features enabled
        if (empty($features)) {
            return '';
        }

        $code = str_replace('%frequency%', wp_json_encode(Settings::getOption('convertext_modwords_density')), $code);
        $code = str_replace('%key%', wp_json_encode(Settings::getOption('website_guid')), $code);
        $code = str_replace('%positive_cls%', wp_json_encode(Settings::getOption('convertext_positive_cls')), $code);
        $code = str_replace('%negative_cls%', wp_json_encode(Settings::getOption('convertext_negative_cls')), $code);
        $code = str_replace(
            '%ignore_domains%',
            wp_json_encode(Settings::getOption('convertext_ignore_domains')),
            $code
        );
        $code = str_replace('%features%', wp_json_encode($features), $code);

        return $code;
    }
}
