<?php
/**
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'pre.php';

try {
    $router = new \Tuleap\Request\FrontRouter();
    $router->route();
} catch (Exception $exception) {
    (new BackendLogger())->error("Unable to route", $exception);
    $GLOBALS['HTML']->rendersError500($exception);
    exit;
}


require_once 'www/forum/forum_utils.php';
require_once 'features_boxes.php';

$hp            = Codendi_HTMLPurifier::instance();
$event_manager = EventManager::instance();

$event_manager->processEvent(Event::DISPLAYING_HOMEPAGE, array());

$current_user              = UserManager::instance()->getCurrentUser();
$current_user_display_name = '';
if ($current_user->isLoggedIn()) {
    $current_user_display_name = $hp->purify(UserHelper::instance()->getDisplayNameFromUser($current_user));
}
if (ForgeConfig::get('sys_https_host')) {
    $login_form_url = 'https://'. ForgeConfig::get('sys_https_host');
} else {
    $login_form_url = 'http://'. ForgeConfig::get('sys_default_domain');
}
$login_form_url .= '/account/login.php';

$display_homepage_boxes      = !isset($GLOBALS['sys_display_homepage_boxes']) || (isset($GLOBALS['sys_display_homepage_boxes']) && $GLOBALS['sys_display_homepage_boxes'] == 1);
$display_homepage_news       = !isset($GLOBALS['sys_display_homepage_news'])  || (isset($GLOBALS['sys_display_homepage_news'])  && $GLOBALS['sys_display_homepage_news']  == 1);
$display_new_account_button  = true;
$event_manager->processEvent('display_newaccount', array('allow' => &$display_new_account_button));
$login_url = '';
$event_manager->processEvent(Event::GET_LOGIN_URL, array('return_to' => '', 'login_url' => &$login_url));

$header_params = array(
    'title' => $Language->getText('homepage', 'title'),
);

Tuleap\Instrument\Collect::increment('service.homepage.accessed');

$homepage_dao = new Admin_Homepage_Dao();
if ($homepage_dao->isStandardHomepageUsed()) {
    $header_params['body_class'] = array('homepage');

    $HTML->header($header_params);
    $request = HTTPRequest::instance();
    $HTML->displayStandardHomepage(
        $display_new_account_button,
        $login_url,
        $request->isSecure()
    );
} else {
    $HTML->header($header_params);

    echo '<div id="homepage" class="container">';
    // go fetch le content that may have its own logic to decide if the boxes should be displayed or not
    ob_start();
    include ($Language->getContent('homepage/homepage', null, null, '.php'));
    $homepage_content = ob_get_contents();
    ob_end_clean();

    echo '<div id="homepage_speech" '. ($display_homepage_boxes ? '' : 'style="width:100%;"') .'>';
    echo $homepage_content;
    echo '</div>';

    if ($display_homepage_boxes) {
        echo '<div id="homepage_boxes">';
        show_features_boxes();
        echo '</div>';
    }

    // HTML is sad, we need to keep this div to clear the "float:right/left" that might exists before
    // Yet another dead kitten somewhere :'(
    echo '<div id="homepage_news">';
    if ($display_homepage_news) {
        $w = new Widget_Static($Language->getText('homepage', 'news_title'));
        $w->setContent(news_show_latest($GLOBALS['sys_news_group'],5,true,false,true,5));
        $w->setRssUrl('/export/rss_sfnews.php');
        $w->display();
    }
    echo '</div>';
    echo '</div>';
}

$HTML->footer(array());
