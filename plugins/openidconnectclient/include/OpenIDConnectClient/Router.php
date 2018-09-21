<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

    /**
     * @var UserMapping\Controller
     */
    private $user_mapping_controller;

    public function __construct(
        Login\Controller $login_controller,
        AccountLinker\Controller $account_linker_controller,
        UserMapping\Controller $user_mapping_controller
    ) {
        $this->login_controller          = $login_controller;
        $this->account_linker_controller = $account_linker_controller;
        $this->user_mapping_controller   = $user_mapping_controller;
    }

    public function route(HTTPRequest $request) {
        $action = $request->get('action');

        if ($action === 'remove-user-mapping') {
            $this->user_mapping_controller->removeMapping($request->get('user_mapping_id'));
            return;
        }

        $this->checkTLSPresence($request);
        switch ($action) {
            case 'link':
                $this->account_linker_controller->showIndex($request);
                break;
            case 'link-existing':
                $this->account_linker_controller->linkExistingAccount($request);
                break;
            default:
                $this->login_controller->login($request, $request->get('return_to'), $request->getTime());
        }
    }

    private function checkTLSPresence(HTTPRequest $request) {
        if(! $request->isSecure()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'only_https_possible')
            );
            $GLOBALS['Response']->redirect('/account/login.php');
        }
    }

}