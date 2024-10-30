<h2><?php use Convertiser\Widgets\Provider\Widgets\PaydayOffersWidget;

_e('Price Comparison Widget', 'convertiser-widgets'); ?></h2>
<p>
    <?php _e(
        'Use the following shortcode in your posts in order to display a payday offers widget',
        'convertiser-widgets'
    ); ?>
</p>

<p><code>[convertiser_payday_offers type="short_term"]</code></p>
<p><strong>type</strong> - <?php _e(
    'refers to payday type and could be either `short_term` or `long_term`',
    'convertiser-widgets'
); ?>.</p>
<hr>
<h3><?php _e('Review list of available arguments:', 'convertiser-widgets'); ?></h3>
<p><?php _e('Use the following options to override default behaviour.', 'convertiser-widgets'); ?></p>
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
            <td><code>widget_label</code></td>
            <td><code>text</code></td>
            <td>
                <?php _e(
                    'Optional widget label that would be reported to Google Analytics. 
                    Requires GA integration to be enabled.',
                    'convertiser-widgets'
                ); ?>
            </td>
        </tr>
        <tr>
            <td><code>type</code></td>
            <td><code>short_term|long_term</code></td>
            <td><?php _e('Filter output by offer type.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>only</code></td>
            <td><code>domain1.com,domain2.com,..</code></td>
            <td><?php _e('Outputs explicitly specified offers only.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>exclude</code></td>
            <td><code>domain1.com,domain2.com,..</code></td>
            <td><?php _e('Exclude specified offers from output.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>customer_age</code></td>
            <td><code>21</code></td>
            <td><?php _e('Filter offers by customer age.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>first_loan_fees</code></td>
            <td><code>yes|no</code></td>
            <td><?php _e('Filter offers by fees policy for new customers.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>blacklist_check</code></td>
            <td><code>yes|no</code></td>
            <td><?php _e('Filter offers by blacklist check policy.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>income_proof</code></td>
            <td><code>yes|no</code></td>
            <td><?php _e('Filter offers by income/work documents check policy.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>loan_amount</code></td>
            <td><code>1000</code></td>
            <td><?php _e('Filter offers by loan amount.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>loan_period</code></td>
            <td><code>60</code></td>
            <td>
                <?php _e(
                    'Filter offers by loan period expressed in days. One month is always equals to 30 days.',
                    'convertiser-widgets'
                ); ?>
            </td>
        </tr>
        <tr>
            <td><code>highlight</code></td>
            <td><code>3</code></td>
            <td><?php _e('Marks first X offers as promoted/recommended.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>show_loan_simulation</code></td>
            <td><code>yes|no</code></td>
            <td>
                <?php _e('Show loan simulation.', 'convertiser-widgets'); ?>
                <?php _e(
                    'Required by Google AdWords rules. Not all templates support this option.',
                    'convertiser-widgets'
                ); ?>
            </td>
        </tr>
        <tr>
            <td><code>show_loan_promotion</code></td>
            <td><code>yes|no</code></td>
            <td>
                <?php _e('Show loan promo text.', 'convertiser-widgets'); ?>
                <?php _e(
                    'Offers may contain optional promo text.',
                    'convertiser-widgets'
                ); ?>
            </td>
        </tr>
        <tr>
            <td><code>template</code></td>
            <td>text</td>
            <td>
                <?php _e(
                    'Specifies template to be used to render payday offers.',
                    'convertiser-widgets'
                ); ?>

                <ul>
                    <?php
                        $templates = apply_filters(
                            'convertiser_widgets_payday_offers_templates',
                            PaydayOffersWidget::getDefaultTemplates()
                        );
                        foreach ($templates as $k => $v) {
                            echo '<li><code>' . $k . '</code></li>';
                        }
                    ?>
                </ul>
            </td>
        </tr>
        <tr>
            <td><code>item_style</code></td>
            <td><code>text</code></td>
            <td>
                <?php _e(
                    'Specifies single item style. Use one of the following:',
                    'convertiser-widgets'
                ); ?>
                <ul>
                    <?php
                    foreach (PaydayOffersWidget::itemColors('item') as $v) {
                        echo '<li><code>' . $v . '</code></li>';
                    }
                    ?>
                </ul>
            </td>
        </tr>
        <tr>
            <td><code>featured_item_style</code></td>
            <td><code>text</code></td>
            <td>
                <?php _e(
                    'Specifies featured item style. Use one of the following:',
                    'convertiser-widgets'
                ); ?>
                <ul>
                    <?php
                    foreach (PaydayOffersWidget::itemColors('featured_item') as $v) {
                        echo '<li><code>' . $v . '</code></li>';
                    }
                    ?>
                </ul>
            </td>
        </tr>
        <tr>
            <td><code>promo_text_style</code></td>
            <td><code>text</code></td>
            <td>
                <?php _e(
                    'Specifies promo text style. Use one of the following:',
                    'convertiser-widgets'
                ); ?>
                <ul>
                    <?php
                    foreach (PaydayOffersWidget::itemColors('promo_text') as $v) {
                        echo '<li><code>' . $v . '</code></li>';
                    }
                    ?>
                </ul>
            </td>
        </tr>
        <tr>
            <td><code>cta_style</code></td>
            <td><code>text</code></td>
            <td>
                <?php _e(
                    'Specifies CTA button style. Use one of the following:',
                    'convertiser-widgets'
                ); ?>
                <ul>
                    <?php
                    foreach (PaydayOffersWidget::itemColors('cta') as $v) {
                        echo '<li><code>' . $v . '</code></li>';
                    }
                    ?>
                </ul>
            </td>
        </tr>
        <tr>
            <td><code>cta_text</code></td>
            <td><code>text</code></td>
            <td>
                <?php _e(
                    'Specifies call to action button text. Supports replacements: {offer} -> offer title, 
                    {domain} -> offer domain, {br} -> line break.',
                    'convertiser-widgets'
                ); ?>
            </td>
        </tr>
        <tr>
            <td><code>limit</code></td>
            <td><code>0..20</code></td>
            <td><?php _e('Limit number of offers in output.', 'convertiser-widgets'); ?></td>
        </tr>
        <tr>
            <td><code>ordering</code></td>
            <td><code>rating</code></td>
            <td>
                <?php _e('Sort offers by one of the available options.', 'convertiser-widgets'); ?>
                <ul>
                    <li><code>rating</code></li>
                    <li><code>max_loan_period</code></li>
                    <li><code>max_loan_amount</code></li>
                    <li><code>first_loan_max_period</code></li>
                    <li><code>first_loan_max_amount</code></li>
                    <li><code>random</code></li>
                </ul>
            </td>
        </tr>
        <tr>
            <td><code>fixed_order</code></td>
            <td><code>domain1.com,domain2.com,..</code></td>
            <td>
<?php _e(
    'Defines one or more offers that should be pinned to the top of the list in a specific order.',
    'convertiser-widgets'
); ?>
            </td>
        </tr>
        </tbody>
    </table>
</div>
