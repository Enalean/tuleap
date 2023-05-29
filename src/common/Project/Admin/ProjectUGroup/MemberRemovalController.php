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

use ArtifactTypeFactory;
use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use ProjectHistoryDao;
use ProjectManager;
use ProjectUGroup;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Project\UGroups\Membership\CannotModifyBoundGroupException;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\DynamicUGroupMembersUpdater;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\MemberRemover;
use Tuleap\Project\UGroups\Membership\StaticUGroups\StaticMemberRemover;
use Tuleap\Project\UserPermissionsDao;
use Tuleap\Project\UserRemover;
use Tuleap\Project\UserRemover as ProjectMemberRemover;
use Tuleap\Project\UserRemoverDao;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;
use UGroupManager;
use UserManager;

class MemberRemovalController implements DispatchableWithRequest
{
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var ProjectAdministratorChecker
     */
    private $administrator_checker;
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

    public function __construct(
        ProjectRetriever $project_retriever,
        ProjectAdministratorChecker $administrator_checker,
        UGroupManager $ugroup_manager,
        UserManager $user_manager,
        MemberRemover $member_remover,
        ProjectMemberRemover $project_member_remover,
        CSRFSynchronizerToken $csrf_synchronizer,
    ) {
        $this->project_retriever      = $project_retriever;
        $this->administrator_checker  = $administrator_checker;
        $this->ugroup_manager         = $ugroup_manager;
        $this->user_manager           = $user_manager;
        $this->member_remover         = $member_remover;
        $this->project_member_remover = $project_member_remover;
        $this->csrf_synchronizer      = $csrf_synchronizer;
    }

    public static function buildSelf(): self
    {
        $ugroup_manager = new UGroupManager();
        $event_manager  = \EventManager::instance();
        $user_manager   = \UserManager::instance();
        return new self(
            ProjectRetriever::buildSelf(),
            new ProjectAdministratorChecker(),
            $ugroup_manager,
            $user_manager,
            new MemberRemover(
                new DynamicUGroupMembersUpdater(
                    new UserPermissionsDao(),
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                    ProjectMemberAdderWithStatusCheckAndNotifications::build(),
                    $event_manager,
                    new ProjectHistoryDao(),
                ),
                new StaticMemberRemover()
            ),
            new UserRemover(
                ProjectManager::instance(),
                $event_manager,
                new ArtifactTypeFactory(false),
                new UserRemoverDao(),
                $user_manager,
                new ProjectHistoryDao(),
                $ugroup_manager,
                new UserPermissionsDao(),
            ),
            UGroupRouter::getCSRFTokenSynchronizer()
        );
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);
        $this->administrator_checker->checkUserIsProjectAdministrator($request->getCurrentUser(), $project);

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
                }
                $this->project_member_remover->removeUserFromProject($ugroup->getProjectId(), $user->getId());
            } else {
                $this->member_remover->removeMember($user, $request->getCurrentUser(), $ugroup);
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
