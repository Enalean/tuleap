<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Layout;

use ForgeConfig;
use HTTPRequest;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class LegacySiteHomePageController implements DispatchableWithRequest
{

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        include_once __DIR__.'/../../www/forum/forum_utils.php';
        include_once __DIR__.'/../../www/include/features_boxes.php';

        $hp            = \Codendi_HTMLPurifier::instance();

        $current_user              = $request->getCurrentUser();
        $current_user_display_name = '';
        if ($current_user->isLoggedIn()) {
            $current_user_display_name = $hp->purify(\UserHelper::instance()->getDisplayNameFromUser($current_user));
        }
        $login_form_url = $request->getServerUrl().'/account/login.php';

        $display_homepage_boxes      = ((int) ForgeConfig::get('sys_display_homepage_boxes') === 1);
        $display_homepage_news       = ((int) ForgeConfig::get('sys_display_homepage_news') === 1);

        $header_params = array(
            'title' => $GLOBALS['Language']->getText('homepage', 'title'),
        );

        $layout->header($header_params);

        echo '<div id="homepage" class="container">';
        // go fetch le content that may have its own logic to decide if the boxes should be displayed or not
        ob_start();
        $Language = $GLOBALS['Language'];
        include($GLOBALS['Language']->getContent('homepage/homepage', null, null, '.php'));
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
            $w = new \Widget_Static($GLOBALS['Language']->getText('homepage', 'news_title'));
            $w->setContent(news_show_latest(ForgeConfig::get('sys_news_group'), 5, true, false, true, 5));
            $w->setRssUrl('/export/rss_sfnews.php');
            $w->display();
        }
        echo '</div>';
        echo '</div>';
    }
}
