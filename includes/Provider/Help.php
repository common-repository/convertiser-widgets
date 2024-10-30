<?php
/**
 * Help docs provider.
 * Add some content to the help tab.
 */
namespace Convertiser\Widgets\Provider;

use Convertiser\Widgets\AbstractProvider;

class Help extends AbstractProvider
{

    /**
     * Hook in tabs.
     */
    public function registerHooks()
    {
        add_action('current_screen', array($this, 'addTabs'), 50);
    }

    /**
     * Add Contextual help tabs
     */
    public function addTabs()
    {
        $screen = get_current_screen();

        $possible_screens = array(
            'settings_page_convertiser-widgets-settings',
        );

        if (! in_array($screen->id, $possible_screens, false)) {
            return;
        }


        ob_start();
        include __DIR__ . '/Help/comparison-widget.php';
        $comparison_help = ob_get_clean();

        ob_start();
        include __DIR__ . '/Help/recommendations-widget.php';
        $recommendations_help = ob_get_clean();

        ob_start();
        include __DIR__ . '/Help/payday-offers-widget.php';
        $payday_offers_help = ob_get_clean();

        $screen->add_help_tab(array(
            'id'      => 'convertiser_widgets_help_comparison',
            'title'   => __('Price Comparison', 'convertiser-widgets'),
            'content' => $comparison_help
        ));

        $screen->add_help_tab(array(
            'id'      => 'convertiser_widgets_help_recommendations',
            'title'   => __('Products Recommendations', 'convertiser-widgets'),
            'content' => $recommendations_help
        ));

        $screen->add_help_tab(array(
            'id'      => 'convertiser_widgets_help_payday_offers',
            'title'   => __('Payday Offers', 'convertiser-widgets'),
            'content' => $payday_offers_help
        ));

        // The following tabs appear on ALL screens.
        // TODO: Add proper help in a next versions of a plugin
//		$screen->add_help_tab( array(
//			'id'	=> 'convertiser_support_tab',
//			'title'	=> __( 'Support', 'convertiser-widgets' ),
//			'content'	=>
//				'<h2>' . __( "Convertiser Support", 'convertiser-widgets' ) . '</h2>' .
//				'<p>' . sprintf(__( 'Find answers to common questions and problems in the <a href="%s?utm_source=plugin&utm_medium=link&utm_campaign=helptab" target="_blank">documentation</a> and in the <a href="%s?utm_source=plugin&utm_medium=link&utm_campaign=helptab" target="_blank">support forum</a>', 'convertiser-widgets' ), CONVERTISER_HELP_URL, CONVERTISER_HELP_URL ) . '. ' . __( 'For additional help, feel free to contact us using the links below.', 'convertiser-widgets' ) . '</p>' .
//				'<p><a href="' . CONVERTISER_HELP_URL . '?utm_source=plugin&utm_medium=link&utm_campaign=helptab" class="button button-primary" target="_blank">' . __( 'Post a Question', 'convertiser-widgets' ) . '</a> (' . __( 'recommended', 'convertiser-widgets' ) . ')</p>' .
//				'<p><a href="' . CONVERTISER_HELP_URL . '?utm_source=plugin&utm_medium=link&utm_campaign=helptab" class="button" target="_blank">' . __( 'Email Us', 'convertiser-widgets' ) . '</a></p>'
//
//		) );
//
//		$screen->add_help_tab( array(
//			'id'	=> 'convertiser_bug_tab',
//			'title'	=> __( 'Found a bug?', 'convertiser-widgets' ),
//			'content'	=>
//				'<h2>' . __( "Found a bug?", 'convertiser-widgets' ) . '</h2>' .
//				'<p>' . sprintf( __( 'If you find a bug within Convertiser, check the <a href="%s?utm_source=plugin&utm_medium=link&utm_campaign=helptab" target="_blank">Bug Reports</a> to see if it’s already been reported. Report a new bug with as much description as possible (context, screenshots, error log, etc.) Thank you!', 'convertiser-widgets' ), CONVERTISER_HELP_URL ) . '</p>' .
//				'<p><a href="' . CONVERTISER_HELP_URL . '?utm_source=plugin&utm_medium=link&utm_campaign=helptab" class="button button-primary" target="_blank">' . __( 'Report a bug', 'convertiser-widgets' ) . '</a></p>'
//
//		) );
//
//		$screen->set_help_sidebar(
//			'<p><strong>' . __( 'For more information:', 'convertiser-widgets' ) . '</strong></p>' .
//			'<p><a href="' . CONVERTISER_HELP_URL . '?utm_source=plugin&utm_medium=link&utm_campaign=helptab" target="_blank">' . __( 'About Convertiser', 'convertiser-widgets' ) . '</a></p>' .
//			'<p><a href="' . CONVERTISER_HELP_URL . '/keys?utm_source=plugin&utm_medium=link&utm_campaign=helptab" target="_blank">' . __( 'API Keys', 'convertiser-widgets' ) . '</a></p>'
//		);
    }
}
