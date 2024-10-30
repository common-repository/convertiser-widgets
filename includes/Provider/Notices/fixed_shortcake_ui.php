<?php
$message = sprintf(
    wp_kses(__('<a href="%1$s" target="%2$s">Shortcode UI</a> (<strong>v0.7.1+</strong>) plugin is not required but it is highly recommended to simplify your experience with Convertiser Widgets UI.', 'convertiser-widgets'),
    array(
        'a' => array('href' => array(), 'target' => array()),
        'strong' => array(),
    )),
    esc_url('https://wordpress.org/plugins/shortcode-ui/'), '_blank'
);
?>
<div class="notice notice-warning">
    <p><?php echo $message; ?></p>
</div>
