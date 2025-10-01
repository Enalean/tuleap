<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost;

use HTTPRequest;
use Override;
use PFUser;
use Feedback;
use Tuleap\BotMattermost\Controller\AdminController;
use Tuleap\Request\DispatchableWithRequest;

class Router implements DispatchableWithRequest
{
    private $admin_controller;

    public function __construct(
        AdminController $admin_controller,
    ) {
        $this->admin_controller = $admin_controller;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @return void
     * @throws \Tuleap\Request\ForbiddenException
     * @throws \Tuleap\Request\NotFoundException
     */
    #[Override]
    public function process(HTTPRequest $request, \Tuleap\Layout\BaseLayout $layout, array $variables)
    {
        $current_user = $request->getCurrentUser();
        $this->checkUserIsSiteAdmin($current_user);

        switch ($request->get('action')) {
            case 'add_bot':
                $this->admin_controller->addBot($request, $layout);
                break;
            case 'edit_bot':
                $this->admin_controller->editBot($request, $layout);
                break;
            case 'delete_bot':
                $this->admin_controller->deleteBot($request, $layout);
                break;
            default:
                $this->admin_controller->displayIndex($layout);
        }
    }

    private function checkUserIsSiteAdmin(PFUser $user)
    {
        if (! $user->isSuperUser()) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('global', 'perm_denied')
            );
            $GLOBALS['Response']->redirect('/');
        }
    }
}
