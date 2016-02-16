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

    /**
     * @var Login\Controller
     */
    private $login_controller;

    /**
     * @var AccountLinker\Controller
     */
    private $account_linker_controller;

    public function __construct(
        Login\Controller $login_controller,
        AccountLinker\Controller $account_linker_controller
    ) {
        $this->login_controller          = $login_controller;
        $this->account_linker_controller = $account_linker_controller;
    }

    public function route(HTTPRequest $request) {
        $this->checkTLSPresence($request);

        switch ($request->get('action')) {
            case 'link':
                $this->account_linker_controller->showIndex($request->get('link_id'), $request->get('return_to'));
                break;
            case 'link-existing':
                $this->account_linker_controller->linkExistingAccount($request);
                break;
            default:
                $this->login_controller->login($request->get('return_to'));
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