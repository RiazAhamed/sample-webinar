<?php

global $post;

// get the webinar
$webinar = WebinarSysteemWebinar::create_from_id($post->ID);

$polyfill_script = WebinarSysteemJS::get_polyfill_path();
$script = WebinarSysteemJS::get_js_path() . '?v=' . WebinarSysteemJS::get_version();
$style = WebinarSysteemJS::get_css_path() . '?v=' . WebinarSysteemJS::get_version();

$boot_data = [
    'locale' => get_locale(),
    'language' => 'en',
    'ajax' => admin_url('admin-ajax.php'),
    'security' => wp_create_nonce(WebinarSysteemJS::get_nonce_secret()),
    'base' => WebinarSysteemJS::get_asset_path(),
    'plugin' => WebinarSysteemJS::get_plugin_path()
];

$is_team_member = current_user_can('manage_options');

$webinar_params = WebinarSysteemRegistrationWidget::get_webinar_info($webinar);
$params = $webinar->get_registration_page_params();
$webinar_extended = [
    'description' => $webinar->get_description()
]

?>
<!DOCTYPE html>
<html class="wpws">
    <head>
        <title><?php echo get_the_title(); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta property="og:title" content="<?php the_title(); ?>">
        <meta property="og:url" content="<?php echo get_permalink($post->ID); ?>">
        <link rel="stylesheet" href="<?php echo plugins_url('./ltd-app-styles.css', __FILE__) ?>" type="text/css" media="all">

        <?php if (WebinarSysteemJS::get_css_path()) { ?>
            <link rel='stylesheet' href="<?= $style ?>" type='text/css' media='all'/>
        <?php } ?>

        <?= $webinar->get_registration_page_head_script_tag() ?>

        <style>
            body {
            <?php if (isset($params->contentBodyBackgroundColor) && strlen($params->contentBodyBackgroundColor) > 0) { ?>
                background-color: <?= $params->contentBodyBackgroundColor ?> !important;
            <?php } else { ?>
                background-color: #fff !important;
            <?php } ?>
            }
        </style>
    </head>

    <body>
    <style type="text/css">
        body {
            overflow: auto;
        }
        .custom-container {
            padding: 0 0 80px 0;
            background: url(<?php echo "'" . plugins_url('../images/custom/webinar-bg.jpg', __FILE__) . "'" ?>);
            background-size: cover;
            background-repeat: no-repeat;
            height: 100%;
            width: 100vw;
            margin: 0;
            overflow: auto;
        }
        .header-container-wrapper {
             padding: 50px 0 40px;
        }
        .client-images-wrapper {
            align-self: center;
            text-align: center;
            margin-top: 0;
        }
        .client-images {
            max-height: 80px;
            width: auto;
            padding: 15px;
        }
        .wpws-registration-wrapper, .wpws-registration-header-content {
            background: transparent !important;
            padding: 0 !important;
        }
        .wpws-registration-header {
            background-image: none !important;
        }
        .wpws-registration-separator {
            display: none;
        }
        .wpws-registration-header-title {
            font-size: 45px  !important;
            color: #333333 !important;
            font-weight: 600 !important;
            padding: 50px 0 !important;
        }
        .wpws-countdown-wrapper {
            border: 1px solid #dddddd;
            box-shadow: inset 0 0 15px #a2a2a2;
            background: #E2E2E2;
        }
        .wpws-countdown-wrapper {
            padding: 0 !important;
        }
        .wpws-countdown-days, wpws-countdown-hours, .wpws-countdown-minutes, .wpws-countdown-seconds, .wpws-countdown-hours {
            margin: 0 !important;
            background: transparent !important;
            padding: 30px 0 20px 0;
            border-right: 1px solid #fff !important;
            border-radius: 0 !important;
        }
        .wpws-countdown-seconds {
            border-width: 0px !important;
        }
        .wpws-countdown-days *, wpws-countdown-hours *, .wpws-countdown-minutes *, .wpws-countdown-seconds *, .wpws-countdown-hours * {
            text-transform: uppercase !important;
            padding-bottom: 10px !important;
            zoom: 1.2;
        }
        .ddpetk {
            max-width: 480px;
        }
        @media (max-width: 480px) {
            .ddpetk {
                max-width: 400px;
            }
        }
        .wpws-registration-main {
            display: none;
        }
        .wpws-registration-sidebar {
            margin: auto;
        }
        .single__wrapper {
            background-color: transparent;
        }
        button.wpws-24 {
            background-color: #ed5844 !important;
            border-radius: 2px;
        }
    </style>
    <div class="grid custom-container">
        <div class="single__wrapper wrapper container container--xlarge container--center">
            <div class="grid header-container-wrapper">
                <div class="grid__column grid__column--12">
                    <div class="grid grid--justify grid--gapless space space--xlarge space--none@large">
                        <div class="client-images-wrapper grid__column grid__column--12 grid__column--4@large space space--large">
                            <img src=<?php echo '"' . plugins_url("../images/custom/LTD_Brandmark_RGB.png", __FILE__) . '"' ?> class="client-images" />
                        </div>
                        <div class="client-images-wrapper grid__column grid__column--6 grid__column--4@large space space--large">
                            <img src=<?php echo '"' . plugins_url("../images/custom/logo-1.png", __FILE__) . '"' ?> class="client-images" />
                        </div>
                        <div class="client-images-wrapper grid__column grid__column--6 grid__column--4@large space space--large">
                             <img src=<?php echo '"' . plugins_url("../images/custom/logo-2.png", __FILE__) . '"' ?> class="client-images" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid__column grid__column--12 webinar-container-wrapper">
            <div id="wpws-register"
                data-params='<?= str_replace('\'', '&apos;', json_encode($params)) ?>''
                data-webinar='<?= str_replace('\'', '&apos;', json_encode($webinar_params)) ?>''
                data-webinar-extended='<?= str_replace('\'', '&apos;', json_encode($webinar_extended)) ?>''
            ></div>
        </div>
    </div>
        <script>
            ___wpws = <?php echo json_encode($boot_data) ?>;
        </script>

        <script src="<?= $polyfill_script ?>"></script>
        <script src="<?= $script ?>"></script>
        <?= $webinar->get_registration_page_body_script_tag() ?>
    </body>
</html>
