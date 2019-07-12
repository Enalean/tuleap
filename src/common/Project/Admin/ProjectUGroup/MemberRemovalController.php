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
use PFUser;
use Project;
use ProjectManager;
use ProjectUGroup;
use Response;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberRemover;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use UGroupManager;
use UserManager;

class MemberRemovalController implements DispatchableWithRequest
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
     * @var DynamicUGroupMembersUpdater
     */
    private $dynamic_ugroup_members_updater;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var StaticMemberRemover
     */
    private $static_member_remover;

    public function __construct(ProjectManager $project_manager, UGroupManager $ugroup_manager, UserManager $user_manager, DynamicUGroupMembersUpdater $dynamic_ugroup_members_updater, StaticMemberRemover $static_member_remover)
    {
        $this->project_manager = $project_manager;
        $this->ugroup_manager  = $ugroup_manager;
        $this->user_manager = $user_manager;
        $this->dynamic_ugroup_members_updater = $dynamic_ugroup_members_updater;
        $this->static_member_remover = $static_member_remover;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout  $layout
     * @param array       $variables
     * @return void
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables) : void
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

        $csrf = new CSRFSynchronizerToken(self::getUrl($ugroup));
        $csrf->check();

        $user = $this->user_manager->getUserById($request->get('remove_user'));
        if (! $user) {
            $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('include_account', 'user_not_exist'));
            throw new NotFoundException();
        }

        $this->removeUserFromUGroup($layout, $project, $ugroup, $user);

        $layout->redirect(UGroupRouter::getUGroupUrl($ugroup));
    }

    private function removeUserFromUGroup(Response $response, Project $project, ProjectUGroup $ugroup, PFUser $user) : void
    {
        if ($ugroup->isBound()) {
            $response->addFeedback(Feedback::ERROR, _('Cannot remove users from bound groups'));
            return;
        }

        if ($ugroup->isStatic()) {
            $this->static_member_remover->removeUser($ugroup, $user);
            return;
        }

        try {
            $this->dynamic_ugroup_members_updater->removeUser($project, $ugroup, $user);
        } catch (CannotRemoveUserMembershipToUserGroupException $ex) {
            $response->addFeedback(
                Feedback::ERROR,
                $ex->getMessage()
            );
        }
    }

    public static function getUrl(ProjectUGroup $ugroup)
    {
        return sprintf('/project/%s/admin/user-group/%s/remove', $ugroup->getProjectId(), $ugroup->getId());
    }
}
