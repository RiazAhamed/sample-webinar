<?php

global $post;

// set the attendee as attended and update the last seen time
$attendee = WebinarSysteemAttendees::get_attendee($post->ID);

// get the webinar
$webinar = WebinarSysteemWebinar::create_from_id($post->ID);

// if we don't have a valid attendee redirect to the registration page..
if ($attendee == null) {
    wp_redirect($webinar->get_url());
    die();
}

$attendee_name = empty($attendee)
    ? ''
    : $attendee->name;

$attendee_name = explode(' ', $attendee_name);

$ajax_url = admin_url('admin-ajax.php');
$cache_url = WebinarSysteemCache::get_cache_url($post->ID, 2);
$reduce_server_load = false;

$webinar_start_time = WebinarSysteem::get_webinar_time($post->ID, $attendee);
$server_time_with_timezone = strtotime(WebinarSysteem::getTimezoneTime($post->ID));
$webinar_time_in_seconds = $server_time_with_timezone - $webinar_start_time;

// is this an automated replay?
$now = $webinar->get_now_in_timezone();

if ($now > $webinar_start_time + $webinar->get_duration() && $webinar->get_automated_replay_enabled()) {
    $webinar_start_time = $now;
    $webinar_time_in_seconds = 0;
}

// update the last active time for this webinar
$webinar->update_last_active_time();

// re-write the cache
WebinarSysteemCache::write_cache($post->ID);

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

$params = [
    'cache_url' => $cache_url,
    'secure_room_name' => $webinar->get_secure_room_name(),
    'secure_room_key' => $webinar->get_secure_room_key(),
    'reduce_server_load' => $reduce_server_load,
    'webinar_time_in_seconds' => $webinar_time_in_seconds,
    'webinar_start_time' => $webinar_start_time,
    'duration' => $webinar->get_duration(),
    'timezone_offset' => $webinar->get_timezone_offset() * 60,
    'attendee' => [
        'id' => (int) $attendee->id,
        'name' => $attendee->name,
        'email' => $attendee->email,
        'is_team_member' => $is_team_member
    ],
    'translations' => WebinarSysteemSettings::instance()->get_translations(),
    'scripts' => [
        'countdown' => $webinar->get_countdown_body_script_tag(),
        'webinar' => $webinar->get_live_page_body_script_tag()
    ]
];

?>
<!DOCTYPE html>
<html class="wpws">
    <head>
        <title><?php echo get_the_title(); ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta property="og:title" content="<?php the_title(); ?>">
        <meta property="og:url" content="<?php echo get_permalink($post->ID); ?>">

        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500">
        <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
        <?php if (WebinarSysteemJS::get_css_path()) { ?>
            <link rel='stylesheet' href="<?= $style ?>" type='text/css' media='all'/>
        <?php } ?>
        <?php if ($webinar->get_live_media_type() === 'twitch') { ?>
            <script src="https://player.twitch.tv/js/embed/v1.js"></script>
        <?php } ?>
        <?php if ($webinar->get_live_media_type() === 'jitsi') { ?>
            <script src='https://meet.jit.si/external_api.js'></script>
        <?php } ?>
        <link rel="stylesheet" href="<?php echo plugins_url('./ltd-app-styles.css', __FILE__) ?>" type="text/css" media="all">
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
            .webinar-container-wrapper {
                padding: 0;
            }
            #wpws-live {
                   min-height: 720px;
                   max-height: 720px;
                   box-shadow: 0 0 40px #a7a7a7;
            }
            .wpws-webinar-toolbar, .wpws-webinar-summary-wrapper, .wpws-webinar-tab-header {
                display: none;
            }
            .align-self--center {
                align-self: center;
            }
            .mejs__container, iframe {
                     /* height: calc(100vh - 500px) !important;
                       min-height: 600px !important;*/
                       max-height: 720px;
                       width: 100%;
                       min-height: 720px;
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
            .wpws-webinar-main, .wpws-webinar-summary-wrapper {
                background: transparent !important;
            }
            .heading {
                font-size: 45px;
                line-height: 60px;
            }
            .wpws-webinar-questions-ask-button {
                background: #ed5844;
                border-radius: 2px;
                padding: 14px 30px;
                text-transform: uppercase;
                font-weight: 500;
            }
            .svg-inline--fa {
                display: none;
            }
            #wpws-player {
                position: absolute;
                width: 100%;
                height: 100%;
            }
            .start-video-text {
                font-size: 27px;
                position: absolute;
                width: 100%;
                height: 100%;
                left: 0;
                top: 0;
                background-color: #fff;
                color: #333333;
                margin: 0;
                display: flex;
                align-items: center;
                place-content: center;
            }
            .sc-cHGsZl.jvPfuB {
                background: #ffffff;
                min-height: 720px;
            }
            @media (max-width: 1200px) {
                .heading, .subtitle {
                    text-align: center !important;
                }
            }
            @media (max-width: 1000px) {
                .mejs__container, iframe, .sc-cHGsZl.jvPfuB {
                    min-height: auto;
                }
            }
        </style>
        <div class="grid custom-container">
            <!-- webinar header -->
            <div class="single__wrapper wrapper container container--xlarge container--center">
                <div class="grid header-container-wrapper">
                    <div class="grid__column grid__column--12 grid__column--7@large">
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
                    <div class="grid__column grid__column--12 grid__column--5@large align-self--center">
                        <h1 class="heading">Title of the talk</h1>
                        <h4 class="subtitle subtitle--primary-font paragraph weight-400 gutter gutter--small">
                            A little Description about what this about.
                        </h4>
                    </div>
                </div>
            </div>
            <!-- webinar container -->
            <div class="grid__column grid__column--12 webinar-container-wrapper">
                <div id="wpws-live" data-params='<?= str_replace('\'', '&apos;', json_encode($params)) ?>''></div>
            </div>
        </div>
            <script>___wpws = <?php echo json_encode($boot_data) ?>;</script>

            <script src="<?= $polyfill_script ?>"></script>
            <script src="<?= $script ?>"></script>

            <!-- placeholder for the body script tag -->
            <div id="body_script"></div>
    </body>
</html>
