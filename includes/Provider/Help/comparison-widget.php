<h2><?php _e('Price Comparison Widget', 'convertiser-widgets'); ?></h2>
<p>
    <?php _e(
        'Use the following shortcode in your posts in order to display a price comparison widget',
        'convertiser-widgets'
    ); ?>
</p>
<p><code>[convertiser_comparison title="iPhone 6 64GB" mpn="MKU62PM/A"]</code></p>
<p><strong>iPhone 6 64GB</strong> - <?php _e('refers to product title', 'convertiser-widgets'); ?>.</p>
<p><strong>MKU62PM/A</strong> - <?php _e('refers to manufacturer product number', 'convertiser-widgets'); ?>.
</p>
<p><?php _e('At least one of this params should be provided.', 'convertiser-widgets'); ?></p>
<hr>
<h4><?php _e('Available arguments:', 'convertiser-widgets'); ?></h4>
<p><?php _e('Use the following options to override default options.', 'convertiser-widgets'); ?></p>
<div class="cvr-widgets">
    <table class="cr--table cr--table-striped cr--table-condensed">
        <thead>
        <tr>
            <th style="width: 20%"><?php _e('Option', 'convertiser-widgets'); ?></th>
            <th style="width: 30%"><?php _e('Values', 'convertiser-widgets'); ?></th>
            <th style="width: 50%"><?php _e('Description', 'convertiser-widgets'); ?></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td><code>ordering</code></td>
            <td><code>asc|desc</code></td>
            <td><?php _e('Direction to sort price offers by price', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>limit</code></td>
            <td><code>0..20</code></td>
            <td><?php _e('Limit number of retailers', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>gallery</code></td>
            <td><code>yes|no</code></td>
            <td><?php _e('Show/hide product pictures', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>card</code></td>
            <td><code>yes|no</code></td>
            <td><?php _e('Show/hide widget border', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>offer_logo</code></td>
            <td><code>yes|no</code></td>
            <td><?php _e('Show/hide retailer logo', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>offer_weight</code></td>
            <td><code>thin|normal|bold|bolder</code></td>
            <td><?php _e('Retailer name font style', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>offer_color</code></td>
            <td><code>#607D8B</code></td>
            <td><?php _e('Retailer name font color', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>price_weight</code></td>
            <td><code>thin|normal|bold|bolder</code></td>
            <td><?php _e('Price font color', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>price_color</code></td>
            <td><code>#4CAF50</code></td>
            <td><?php _e('Price font style', 'convertiser-widgets'); ?></td>
        </tr>
        </tbody>
    </table>
</div>
