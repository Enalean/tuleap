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

use HTTPRequest;
use ForgeConfig;
use Event;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class SiteHomepageController implements DispatchableWithRequest, DispatchableWithBurningParrot
{

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $event_manager = \EventManager::instance();

        $event_manager->processEvent(Event::DISPLAYING_HOMEPAGE, array());

        $display_new_account_button  = true;
        $event_manager->processEvent('display_newaccount', array('allow' => &$display_new_account_button));
        $login_url = '';
        $event_manager->processEvent(\Event::GET_LOGIN_URL, array('return_to' => '', 'login_url' => &$login_url));

        $header_params = array(
            'title' => $GLOBALS['Language']->getText('homepage', 'title'),
        );

        $header_params['body_class'] = array('homepage');

        $layout->header($header_params);
        $layout->displayStandardHomepage(
            $display_new_account_button,
            $login_url,
            $request->isSecure()
        );
        $layout->footer(array());
    }
}
