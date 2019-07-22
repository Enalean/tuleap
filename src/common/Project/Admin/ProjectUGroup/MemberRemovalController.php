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
use ProjectUGroup;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\UserRemover as ProjectMemberRemover;
use Tuleap\Project\UGroups\Membership\CannotModifyBoundGroupException;
use Tuleap\Project\UGroups\Membership\MemberRemover;
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
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var MemberRemover
     */
    private $member_remover;
    /**
     * @var ProjectMemberRemover
     */
    private $project_member_remover;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_synchronizer;

    public function __construct(ProjectManager $project_manager, UGroupManager $ugroup_manager, UserManager $user_manager, MemberRemover $member_remover, ProjectMemberRemover $project_member_remover, CSRFSynchronizerToken $csrf_synchronizer)
    {
        $this->project_manager = $project_manager;
        $this->ugroup_manager  = $ugroup_manager;
        $this->user_manager    = $user_manager;
        $this->member_remover  = $member_remover;
        $this->project_member_remover = $project_member_remover;
        $this->csrf_synchronizer = $csrf_synchronizer;
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

        $this->csrf_synchronizer->check(UGroupRouter::getUGroupUrl($ugroup));

        $user = $this->user_manager->getUserById($request->get('remove_user'));
        if (! $user) {
            $layout->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('include_account', 'user_not_exist'));
            throw new NotFoundException();
        }

        try {
            if ($request->get('remove-from-ugroup') === 'remove-from-ugroup-and-project') {
                if ($user->isAdmin($ugroup->getProjectId())) {
                    $layout->addFeedback(Feedback::ERROR, _('Cannot remove project admin from project. Must be removed from project administration first'));
                    $layout->redirect(UGroupRouter::getUGroupUrl($ugroup));
                    return;
                }
                $this->project_member_remover->removeUserFromProject($ugroup->getProjectId(), $user->getId());
            } else {
                $this->member_remover->removeMember($user, $ugroup);
            }
        } catch (CannotModifyBoundGroupException $exception) {
            $layout->addFeedback(Feedback::ERROR, _('Cannot remove users from bound groups'));
        } catch (CannotRemoveUserMembershipToUserGroupException $exception) {
            $layout->addFeedback(Feedback::ERROR, _('Cannot remove user from group'));
        }

        $layout->redirect(UGroupRouter::getUGroupUrl($ugroup));
    }

    public static function getUrl(ProjectUGroup $ugroup)
    {
        return sprintf('/project/%s/admin/user-group/%s/remove', $ugroup->getProjectId(), $ugroup->getId());
    }
}
