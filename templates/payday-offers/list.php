<?php

use function Convertiser\Widgets\Provider\Widgets\format_bool;
use function Convertiser\Widgets\Provider\Widgets\format_days;
use function Convertiser\Widgets\Provider\Widgets\get_analytics_non_interactive_event;
use function Convertiser\Widgets\Provider\Widgets\get_analytics_onclick_event;
use function Convertiser\Widgets\Provider\Widgets\get_cta_text;
use function Convertiser\Widgets\Provider\Widgets\get_formatted_loan_amount;
use function Convertiser\Widgets\Provider\Widgets\get_formatted_loan_length;
use function Convertiser\Widgets\Provider\Widgets\get_logo_css;
use function Convertiser\Widgets\Provider\Widgets\get_tracking_url;

$i = 0;

// Render GA event
/** @var string $label */
echo get_analytics_non_interactive_event($label, 'impression', '');
?>

<section class="cr--payday-list">
<?php
/** @var array $offers */
foreach ($offers as &$offer) :
    $i++;
    $id  = str_replace('.', '_', $offer['domain']);
    $url = get_tracking_url($offer, $atts['widget_label']);

    $offer['_id'] = $id;

    $loanAmount = get_formatted_loan_amount($offer['min_loan_amount'], $offer['max_loan_amount']);
    $loanLength = get_formatted_loan_length($offer['min_loan_period'], $offer['max_loan_period']);

    $firstLoanMaxAmount = number_format($offer['first_loan_max_amount'], 0, ',', '.');
    $firstLoanMaxLength = format_days($offer['first_loan_max_period']);
    $onClick            = get_analytics_onclick_event($label, 'click', $offer['title'], $i);

    $featured = $i <= $atts['highlight'] ? 'cr--payday-list__item--featured' . ' ' . $featuredItemStyle : '';
    $ctaText  = get_cta_text(
        'Otrzymaj <br class="cr--payday-list__item__cta__button__divider"> Pożyczkę',
        $offer['title'],
        $offer['domain'],
        $atts['cta_text']
    );
    ?>
    <section
        class="cr--payday-list__item <?php echo esc_attr($featured !== '' ? $featured : $itemStyle); ?>"
        id="payday_offer_<?php echo esc_attr($id); ?>"
        data-params="<?php echo htmlspecialchars(json_encode($offer), ENT_QUOTES, 'UTF-8'); ?>">

    <?php if (strlen($offer['promotion']) > 0 && $atts['show_loan_promotion']) : ?>
        <div
            class="cr--payday-list__item__promo <?php echo esc_attr($promoTextStyle);?>"
            title="<?php echo esc_attr($offer['promotion']); ?>">
            <div class="cr--payday-list__item__promo-text">
                <?php echo esc_html($offer['promotion']); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="cr--payday-list__item__wrapper">
        <div class="cr--payday-list__item__logo">
            <div class="cr--payday-list__item__padder">
                <a <?php echo $onClick; ?>
                    href="<?php echo esc_url($url); ?>"
                    rel="nofollow"
                    class="cr--payday-list__item__logo__link"
                    target="_blank"
                    style="<?php echo esc_attr(get_logo_css($offer['logo'])); ?>"
                    title="Sprawdź ofertę <?php echo esc_attr($offer['title']); ?>!"
                ></a>
            </div>
        </div>

        <div class="cr--payday-list__item__properties__amount">
            <div class="cr--payday-list__item__padder">
                <div class="cr--payday-list__item__properties__name">Kwota pożyczki:</div>
                <div class="cr--payday-list__item__properties__value"><?php echo esc_html($loanAmount); ?></div>
                <div class="cr--payday-list__item__properties__note <?php echo esc_attr($featured !== '' ? $featuredNoteStyle : $noteStyle); ?>">
                    Pierwsza do <?php echo esc_html(sprintf('%s %s', $firstLoanMaxAmount, 'zł')); ?>
                </div>
            </div>
        </div>

        <div class="cr--payday-list__item__properties__period">
            <div class="cr--payday-list__item__padder">
                <div class="cr--payday-list__item__properties__name">Czas na spłatę:</div>
                <div class="cr--payday-list__item__properties__value"><?php echo esc_html($loanLength); ?></div>
                <div class="cr--payday-list__item__properties__note <?php echo esc_attr($featured !== '' ? $featuredNoteStyle : $noteStyle); ?>">
                    Pierwsza do <?php echo esc_html(sprintf('%s %s', $firstLoanMaxLength[0], $firstLoanMaxLength[2])); ?>
                </div>
            </div>
        </div>

        <div class="cr--payday-list__item__properties__terms">
            <div class="cr--payday-list__item__padder">
                <div>
                    <div class="cr--payday-list__item__properties__name--plain"
                         data-delay="500"
                         data-toggle="tooltip"
                         title="Pierwsza pożyczka jest udzielana bez prowizji">
                        Darmowa:
                        <strong><?php echo format_bool(!$offer['first_loan_fees']); ?></strong>
                    </div>
                </div>
                <div>
                    <div class="cr--payday-list__item__properties__name--plain"
                         data-delay="500"
                         data-toggle="tooltip"
                         title="Klient nie jest sprawdzany w Biurze Informacji Kredytowej">
                        Bez BIK:
                        <strong><?php echo format_bool(!$offer['customer_blacklist_check']); ?></strong>
                    </div>
                </div>
                <div>
                    <div class="cr--payday-list__item__properties__name--plain"
                         data-delay="500"
                         data-toggle="tooltip"
                         title="Zaświadczenie o dochodach nie jest wymagane">
                        Bez zaświadczeń:
                        <strong><?php echo format_bool(!$offer['customer_income_check']); ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="cr--payday-list__item__cta">
            <div class="cr--payday-list__item__padder">
                <a <?php echo $onClick; ?>
                    href="<?php echo esc_url($url); ?>"
                    rel="nofollow"
                    target="_blank"
                    class="cr--payday-list__item__cta__button <?php echo esc_attr($ctaStyle);?>">
                    <?php echo $ctaText; ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (strlen($offer['loan_simulation']) > 0 && $atts['show_loan_simulation']) : ?>
        <div class="cr--payday-list__item__loan-simulation <?php echo esc_attr($featured !== '' ? $featuredSimulationStyle : $simulationStyle); ?>">
            <div class="cr--payday-list__item__padder">
                <?php echo esc_html($offer['loan_simulation']); ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php endforeach; unset($offer); ?>
</section>
