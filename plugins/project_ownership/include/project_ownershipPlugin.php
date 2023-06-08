<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use Tuleap\admin\ProjectEdit\ProjectStatusUpdate;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\Admin\ForceRemovalOfRestrictedAdministrator;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\ApproveProjectAdministratorRemoval;
use Tuleap\Project\Admin\ProjectUGroup\ProjectImportCleanupUserCreatorFromAdministrators;
use Tuleap\Project\Admin\ProjectUGroup\ProjectUGroupMemberUpdatable;
use Tuleap\Project\Admin\Visibility\UpdateVisibilityIsAllowedEvent;
use Tuleap\Project\Registration\RegisterProjectCreationEvent;
use Tuleap\ProjectOwnership\ProjectAdmin\CannotRemoveProjectOwnerFromTheProjectAdministratorsException;
use Tuleap\ProjectOwnership\ProjectAdmin\IndexController;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerDAO;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerRemover;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerRetriever;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerUpdater;
use Tuleap\ProjectOwnership\ProjectOwner\UserWithStarBadgeFinder;
use Tuleap\ProjectOwnership\ProjectOwner\XMLProjectImportUserCreatorProjectOwnerCleaner;
use Tuleap\ProjectOwnership\REST\ProjectOwnershipResource;
use Tuleap\ProjectOwnership\SystemEvents\ProjectOwnershipSystemEventManager;
use Tuleap\ProjectOwnership\SystemEvents\ProjectOwnerStatusNotificationSystemEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Widget\Event\UserWithStarBadgeCollector;

require_once __DIR__ . '/../vendor/autoload.php';

class project_ownershipPlugin extends Plugin // phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-project_ownership', __DIR__ . '/../site-content');
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\ProjectOwnership\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function registerProjectCreationEvent(RegisterProjectCreationEvent $event): void
    {
        $dao = new ProjectOwnerDAO();
        $dao->save(
            (int) $event->getJustCreatedProject()->getID(),
            (int) $event->getProjectAdministrator()->getId(),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $params['restler']->addAPIClass(ProjectOwnershipResource::class, 'project_ownership');
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter): void
    {
        $project_id = $presenter->getProjectId();
        $html_url   = $this->getPluginPath() . '/project/' . urlencode($project_id) . '/admin';
        $presenter->addDropdownItem(
            NavigationPresenterBuilder::OTHERS_ENTRY_SHORTNAME,
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-project_ownership', 'Project ownership'),
                $html_url
            )
        );
    }

    public function routeGetProjectAdmin(): IndexController
    {
        return IndexController::buildSelf();
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $routes): void
    {
        $routes->getRouteCollector()->addGroup(
            $this->getPluginPath(),
            function (FastRoute\RouteCollector $r) {
                $r->get(
                    '/project/{project_id:\d+}/admin',
                    $this->getRouteHandler('routeGetProjectAdmin')
                );
            }
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectUGroupMemberUpdatable(ProjectUGroupMemberUpdatable $ugroup_member_update): void
    {
        $ugroup = $ugroup_member_update->getGroup();
        if (
            $ugroup->getId() !== ProjectUGroup::PROJECT_ADMIN &&
            $ugroup->getId() !== ProjectUGroup::PROJECT_MEMBERS
        ) {
            return;
        }
        $project_owner_retriever = new ProjectOwnerRetriever(new ProjectOwnerDAO(), UserManager::instance());
        $project_owner           = $project_owner_retriever->getProjectOwner($ugroup->getProject());
        if ($project_owner === null) {
            return;
        }
        foreach ($ugroup_member_update->getMembers() as $member) {
            if ($member->getId() === $project_owner->getId()) {
                $ugroup_member_update->markUserHasNotUpdatable(
                    $project_owner,
                    dgettext('tuleap-project_ownership', 'The project owner cannot be removed.')
                );
                return;
            }
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function approveProjectAdministratorRemoval(ApproveProjectAdministratorRemoval $project_administrator_removal): void
    {
        $project_owner_retriever = new ProjectOwnerRetriever(new ProjectOwnerDAO(), UserManager::instance());
        $project_owner           = $project_owner_retriever->getProjectOwner(
            $project_administrator_removal->getProject()
        );
        if ($project_owner === null) {
            return;
        }

        if ($project_owner->getId() === $project_administrator_removal->getUserToRemove()->getId()) {
            throw new CannotRemoveProjectOwnerFromTheProjectAdministratorsException(
                $project_administrator_removal->getProject(),
                $project_owner
            );
        }
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectImportCleanupUserCreatorFromAdministrators(
        ProjectImportCleanupUserCreatorFromAdministrators $cleanup_user_creator_from_administrators,
    ): void {
        $xml_project_user_creator_project_owner_updater = new XMLProjectImportUserCreatorProjectOwnerCleaner(
            new ProjectOwnerUpdater(
                new ProjectOwnerDAO(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            )
        );

        $xml_project_user_creator_project_owner_updater->updateProjectOwnership($cleanup_user_creator_from_administrators);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function userWithStarBadgeCollector(UserWithStarBadgeCollector $collector): void
    {
        $dao    = new ProjectOwnerDAO();
        $finder = new UserWithStarBadgeFinder($dao);
        $finder->findBadgedUser($collector);
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::GET_SYSTEM_EVENT_CLASS)]
    public function getSystemEventClass($params): void
    {
        switch ($params['type']) {
            case ProjectOwnerStatusNotificationSystemEvent::NAME:
                $params['class']        = ProjectOwnerStatusNotificationSystemEvent::class;
                $params['dependencies'] = [
                    new \Tuleap\ProjectOwnership\Notification\Sender(\ProjectManager::instance(), new \Tuleap\Language\LocaleSwitcher()),
                ];
                break;
        }
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE)]
    public function systemEventGetTypesForDefaultQueue(array &$params): void
    {
        $params['types'] = array_merge($params['types'], [ProjectOwnerStatusNotificationSystemEvent::NAME]);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function projectStatusUpdate(ProjectStatusUpdate $event): void
    {
        $this->getSystemEventManager()->queueNotifyProjectStatusChange(
            $event->project->getID(),
            $event->status
        );
    }

    private function getSystemEventManager()
    {
        return new ProjectOwnershipSystemEventManager(
            SystemEventManager::instance()
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function updateVisibilityIsAllowedEvent(UpdateVisibilityIsAllowedEvent $event): void
    {
        $update_status = (new Tuleap\ProjectOwnership\ProjectAdmin\UpdateVisibilityChecker(
            new ProjectOwnerRetriever(new ProjectOwnerDAO(), UserManager::instance()),
        ))->canUpdateVisibilityRegardingRestrictedUsers($event->getProject());

        $event->setUpdateVisibilityStatus($update_status);
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function forceRemovalOfRestrictedAdministrator(ForceRemovalOfRestrictedAdministrator $event): void
    {
        (new ProjectOwnerRemover(
            new ProjectOwnerDAO(),
            new ProjectOwnerRetriever(new ProjectOwnerDAO(), UserManager::instance()),
            BackendLogger::getDefaultLogger(),
        ))->forceRemovalOfRestrictedProjectOwner(
            $event->project,
            $event->user,
        );
    }
}
