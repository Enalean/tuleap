<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\Layout\BaseLayout;
use Tuleap\OpenIDConnectClient\Administration\Controller;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class AdminRouter implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var Controller
     */
    private $controller;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(Controller $controller, CSRFSynchronizerToken $csrf_token)
    {
        $this->controller = $controller;
        $this->csrf_token = $csrf_token;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        $this->checkUserIsSiteAdmin($current_user, $layout);

        $action = $request->get('action');
        switch ($action) {
            case 'create-generic-provider':
                $this->controller->createGenericProvider($this->csrf_token, $request);
                break;
            case 'create-azure-provider':
                $this->controller->createAzureADProvider($this->csrf_token, $request);
                break;
            case 'update-generic-provider':
                $this->controller->updateGenericProvider($this->csrf_token, $request);
                break;
            case 'update-azure-provider':
                $this->controller->updateAzureProvider($this->csrf_token, $request);
                break;
            case 'delete-provider':
                $this->controller->removeProvider($this->csrf_token, $request->get('provider_id'), $current_user);
                break;
            default:
                $this->controller->showAdministration($this->csrf_token, $current_user);
        }
    }

    private function checkUserIsSiteAdmin(PFUser $user, BaseLayout $layout)
    {
        if (! $user->isSuperUser()) {
            $layout->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );
            $layout->redirect('/');
        }
    }
}
