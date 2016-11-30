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


use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use PFUser;
use Tuleap\OpenIDConnectClient\Administration\Controller;

class AdminRouter {
    /**
     * @var Controller
     */
    private $controller;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(Controller $controller, CSRFSynchronizerToken $csrf_token) {
        $this->controller = $controller;
        $this->csrf_token = $csrf_token;
    }

    public function route(HTTPRequest $request) {
        $current_user = $request->getCurrentUser();
        $this->checkUserIsSiteAdmin($current_user);

        $action = $request->get('action');
        switch ($action) {
            case 'create-provider':
                $this->controller->createProvider($this->csrf_token, $request);
                break;
            case 'update-provider':
                $this->controller->updateProvider($this->csrf_token, $request);
                break;
            case 'delete-provider':
                $this->controller->removeProvider($this->csrf_token, $request->get('provider_id'), $current_user);
                break;
            default:
                $this->controller->showAdministration($this->csrf_token, $current_user);
        }
    }

    private function checkUserIsSiteAdmin(PFUser $user) {
        if(! $user->isSuperUser()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );
            $GLOBALS['Response']->redirect('/');
        }
    }
}