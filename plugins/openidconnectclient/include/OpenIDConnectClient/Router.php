<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient;

use Feedback;
use ForgeConfig;
use HTTPRequest;

class Router {

    private $login_controller;

    public function __construct(LoginController $login_controller) {
        $this->login_controller = $login_controller;
    }

    public function route(HTTPRequest $request) {
        $this->checkTLSPresence($request);

        if ($request->exist('code')) {
            $this->login_controller->login();
        } else {
            $this->login_controller->displayLoginLink();
        }
    }

    private function checkTLSPresence(HTTPRequest $request) {
        if(! $request->isSSL()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'only_https_possible')
            );
            $GLOBALS['Response']->redirect('https://' . ForgeConfig::get('sys_https_host') . '/account/login.php');
        }
    }

}