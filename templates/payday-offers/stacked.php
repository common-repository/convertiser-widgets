<?php
use function Convertiser\Widgets\Provider\Widgets\format_days;
use function Convertiser\Widgets\Provider\Widgets\get_analytics_non_interactive_event;
use function Convertiser\Widgets\Provider\Widgets\get_analytics_onclick_event;
use function Convertiser\Widgets\Provider\Widgets\get_cta_text;
use function Convertiser\Widgets\Provider\Widgets\get_formatted_loan_amount;
use function Convertiser\Widgets\Provider\Widgets\get_formatted_loan_length;
use function Convertiser\Widgets\Provider\Widgets\get_logo_css;
use function Convertiser\Widgets\Provider\Widgets\get_tracking_url;

$i     = 0;

// Render GA event
/** @var string $label */
echo get_analytics_non_interactive_event($label, 'impression', '');
?>

<section class="cr--payday-stacked">
    <?php
    /** @var array $offers */
    foreach ($offers as $offer) :
        $i++;
        $id = str_replace('.', '_', $offer['domain']);
        $url = get_tracking_url($offer, $atts['widget_label']);

        $loanAmount = get_formatted_loan_amount($offer['min_loan_amount'], $offer['max_loan_amount']);
        $loanLength = get_formatted_loan_length($offer['min_loan_period'], $offer['max_loan_period']);

        $firstLoanMaxAmount = number_format($offer['first_loan_max_amount'], 0, ',', '.');
        $firstLoanMaxLength = format_days($offer['first_loan_max_period']);
        $onClick            = get_analytics_onclick_event($label, 'click', $offer['title'], $i);

        /** @var array $atts */
        $featured = $i <= $atts['highlight'] ? 'cr--payday-stacked__item--featured' . ' ' . $featuredItemStyle : '';
        $maxDays  = format_days($offer['max_loan_period']);
        $ctaText  = get_cta_text('Sprawdź ofertę!', $offer['title'], $offer['domain'], $atts['cta_text']);
    ?>
    <section
        class="cr--payday-stacked__item <?php echo esc_attr($featured !== '' ? $featured : $itemStyle); ?>"
        id="payday_offer_<?php echo esc_attr($id);?>">
    <div class="cr--payday-stacked__item__wrapper">
        <div class="cr--payday-stacked__item__logo">
            <div class="cr--payday-stacked__item__padder">
                <a <?php echo $onClick; ?>
                    href="<?php echo esc_url($url); ?>"
                    rel="nofollow"
                    title="<?php echo esc_attr($offer['loan_simulation']); ?>"
                    class="cr--payday-stacked__item__logo__link"
                    target="_blank"
                    style="<?php echo esc_attr(get_logo_css($offer['logo'])); ?>"
                    title="Sprawdź ofertę <?php echo esc_attr($offer['title']); ?>!"
                ></a>
            </div>
        </div>

        <div class="cr--payday-stacked__item__properties">
            <div class="cr--payday-stacked__item__padder">
                <div>
                    <span class="cr--payday-stacked__item__properties__text">Pożycz do</span>
                    <span class="cr--payday-stacked__item__properties__amount">
                        <?php echo esc_html(number_format($offer['max_loan_amount'], 0, ',', '.') . 'zł'); ?>
                    </span>
                </div>
                <div>
                    <span class="cr--payday-stacked__item__properties__text">na</span>
                    <span class="cr--payday-stacked__item__properties__days">
                        <?php printf('%s %s', $maxDays[0], $maxDays[2]); ?>
                    </span>
                    <span class="cr--payday-stacked__item__properties__intcentive">
                    <?php if ($offer['first_loan_fees']) : ?>
                        za darmo!
                    <?php endif; ?>
                    </span>
                </div>

                <div class="cr--payday-stacked__item__cta">
                    <div class="cr--payday-stacked__item__padder">
                        <a <?php echo $onClick; ?>
                            href="<?php echo esc_url($url); ?>"
                            rel="nofollow"
                            target="_blank"
                            title="<?php echo esc_attr($offer['loan_simulation']); ?>"
                            class="cr--payday-stacked__item__cta__button <?php echo esc_attr($ctaStyle);?>">
                            <?php echo $ctaText; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>

</section>

<?php endforeach; ?>
</section>
