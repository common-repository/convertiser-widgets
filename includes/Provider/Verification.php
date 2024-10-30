<?php
/**
 * Verification provider.
 */
namespace Convertiser\Widgets\Provider;

use Convertiser\Widgets\AbstractProvider;
use function Convertiser\Widgets\getPlugin;

/**
 * Verification provider class.
 */
class Verification extends AbstractProvider
{
    /**
     * Register hook to display verification token.
     */
    public function registerHooks()
    {
        add_action('wp_head', array($this, 'showVerificationToken'));
    }

    /**
     * Display verification token rendered in site header
     */
    public function showVerificationToken()
    {
        if (getPlugin()->isConfigured() && Settings::getOption('display_verification_code') === 'yes') {
            $out = sprintf('<!-- convertiser-verification: %s -->', Settings::getOption('website_guid'));
            echo $out;
        }
    }
}
