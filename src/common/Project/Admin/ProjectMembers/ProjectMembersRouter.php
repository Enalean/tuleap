<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\ProjectMembers;

use CSRFSynchronizerToken;
use EventManager;
use HTTPRequest;

class ProjectMembersRouter
{
    /**
     * @var ProjectMembersController
     */
    private $members_controller;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        ProjectMembersController $members_controller,
        CSRFSynchronizerToken $csrf_token,
        EventManager $event_manager
    ) {
        $this->members_controller = $members_controller;
        $this->csrf_token         = $csrf_token;
        $this->event_manager      = $event_manager;
    }

    public function route(HTTPRequest $request)
    {
        switch ($request->get('action')) {
            case 'add-user':
                $this->members_controller->addUserToProject($request);
                $this->redirect($request);
                break;

            case 'remove-user':
                $this->members_controller->removeUserFromProject($request);
                $this->redirect($request);
                break;
            case 'import':
                $this->csrf_token->check();
                $this->members_controller->importMembers($request);
                $this->redirect($request);
                break;
            default:
                $event = new MembersEditProcessAction(
                    $request,
                    $this->csrf_token
                );

                $this->event_manager->processEvent($event);
                if ($event->hasBeenHandled()) {
                    $this->redirect($request);
                } else {
                    $this->members_controller->display($request);
                }

                break;
        }
    }

    private function redirect(HTTPRequest $request)
    {
        $GLOBALS['Response']->redirect(
            '/project/admin/members.php?group_id=' . urlencode(
                $request->getProject()->getID()
            )
        );
    }
}
