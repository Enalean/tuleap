<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\ProjectUGroup;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use ProjectManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use UGroupManager;
use UserManager;

class MemberAdditionController implements DispatchableWithRequest
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_synchronizer;
    /**
     * @var MemberAdder
     */
    private $member_adder;

    public function __construct(ProjectManager $project_manager, UGroupManager $ugroup_manager, UserManager $user_manager, MemberAdder $member_adder, CSRFSynchronizerToken $csrf_synchronizer)
    {
        $this->project_manager = $project_manager;
        $this->ugroup_manager = $ugroup_manager;
        $this->user_manager = $user_manager;
        $this->csrf_synchronizer = $csrf_synchronizer;
        $this->member_adder = $member_adder;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project_id = $variables['id'];
        $project = $this->project_manager->getProject($project_id);
        if (! $project || $project->isError()) {
            throw new NotFoundException();
        }

        if (! $request->getCurrentUser()->isAdmin($project->getID())) {
            throw new ForbiddenException();
        }

        $ugroup = $this->ugroup_manager->getUGroup($project, $variables['user-group-id']);
        if (! $ugroup) {
            throw new NotFoundException();
        }

        $this->csrf_synchronizer->check(UGroupRouter::getUGroupUrl($ugroup));

        $is_update_allowed = ! $ugroup->isBound();
        if (! $is_update_allowed) {
            $layout->redirect(UGroupRouter::getUGroupUrl($ugroup));
            return;
        }

        $add_user_name = $request->get('add_user_name');
        if (! $add_user_name) {
            $layout->redirect(UGroupRouter::getUGroupUrl($ugroup));
            return;
        }
        $user = $this->user_manager->findUser($add_user_name);
        if (! $user) {
            $layout->addFeedback(Feedback::ERROR, _('User does not exist'));
            $layout->redirect(UGroupRouter::getUGroupUrl($ugroup));
            return;
        }

        try {
            $this->member_adder->addMember($user, $ugroup);
        } catch (CannotAddRestrictedUserToProjectNotAllowingRestricted $ex) {
            $layout->addFeedback(
                Feedback::ERROR,
                sprintf(
                    _('Account %s is restricted and the project does not allow restricted users. User not added.'),
                    $ex->getRestrictedUser()->getUserName()
                )
            );
        }
        $layout->redirect(UGroupRouter::getUGroupUrl($ugroup));
    }
}
