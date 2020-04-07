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

use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Project\Admin\Navigation\NavigationItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationPresenter;
use Tuleap\Project\Admin\ProjectUGroup\ApproveProjectAdministratorRemoval;
use Tuleap\Project\Admin\ProjectUGroup\ProjectImportCleanupUserCreatorFromAdministrators;
use Tuleap\Project\Admin\ProjectUGroup\ProjectUGroupMemberUpdatable;
use Tuleap\ProjectOwnership\ProjectAdmin\CannotRemoveProjectOwnerFromTheProjectAdministratorsException;
use Tuleap\ProjectOwnership\ProjectAdmin\IndexController;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerDAO;
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

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REGISTER_PROJECT_CREATION);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(NavigationPresenter::NAME);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(ProjectUGroupMemberUpdatable::NAME);
        $this->addHook(ApproveProjectAdministratorRemoval::NAME);
        $this->addHook(ProjectImportCleanupUserCreatorFromAdministrators::NAME);
        $this->addHook(UserWithStarBadgeCollector::NAME);
        $this->addHook('project_is_suspended');
        $this->addHook('project_is_active');
        $this->addHook('project_is_deleted');

        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);

        return parent::getHooksAndCallbacks();
    }

    /**
     * @see \Event::REGISTER_PROJECT_CREATION
     */
    public function registerProjectCreation(array $params)
    {
        $dao = new ProjectOwnerDAO();
        $dao->save($params['group_id'], $params['project_administrator']->getId());
    }

    /**
     * @see \Event::REST_RESOURCES
     */
    public function restResources(array $params)
    {
        $params['restler']->addAPIClass(ProjectOwnershipResource::class, 'project_ownership');
    }

    public function collectProjectAdminNavigationItems(NavigationPresenter $presenter)
    {
        $project_id = $presenter->getProjectId();
        $html_url = $this->getPluginPath() . '/project/' . urlencode($project_id) . '/admin';
        $presenter->addItem(
            new NavigationItemPresenter(
                dgettext('tuleap-project_ownership', 'Project ownership'),
                $html_url,
                IndexController::PANE_SHORTNAME,
                $presenter->getCurrentPaneShortname()
            )
        );
    }

    public function routeGetProjectAdmin(): IndexController
    {
        return IndexController::buildSelf();
    }

    public function collectRoutesEvent(CollectRoutesEvent $routes)
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

    public function projectUGroupMemberUpdatable(ProjectUGroupMemberUpdatable $ugroup_member_update)
    {
        $ugroup = $ugroup_member_update->getGroup();
        if (
            (int) $ugroup->getId() !== ProjectUGroup::PROJECT_ADMIN &&
            (int) $ugroup->getId() !== ProjectUGroup::PROJECT_MEMBERS
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

    public function approveProjectAdministratorRemoval(ApproveProjectAdministratorRemoval $project_administrator_removal)
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

    public function projectImportCleanupUserCreatorFromAdministrators(
        ProjectImportCleanupUserCreatorFromAdministrators $cleanup_user_creator_from_administrators
    ): void {
        $xml_project_user_creator_project_owner_updater = new XMLProjectImportUserCreatorProjectOwnerCleaner(
            new ProjectOwnerUpdater(
                new ProjectOwnerDAO(),
                new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection())
            )
        );

        $xml_project_user_creator_project_owner_updater->updateProjectOwnership($cleanup_user_creator_from_administrators);
    }

    public function userWithStarBadgeCollector(UserWithStarBadgeCollector $collector)
    {
        $dao    = new ProjectOwnerDAO();
        $finder = new UserWithStarBadgeFinder($dao, $GLOBALS['Language']);
        $finder->findBadgedUser($collector);
    }

    public function get_system_event_class($params) // phpcs:ignore
    {
        switch ($params['type']) {
            case ProjectOwnerStatusNotificationSystemEvent::NAME:
                $params['class'] = ProjectOwnerStatusNotificationSystemEvent::class;
                $params['dependencies'] = [
                    new \Tuleap\ProjectOwnership\Notification\Sender(\ProjectManager::instance())
                ];
                break;
        }
    }

    public function system_event_get_types_for_default_queue(array &$params) // phpcs:ignore
    {
        $params['types'] = array_merge($params['types'], [ProjectOwnerStatusNotificationSystemEvent::NAME]);
    }

    public function project_is_suspended(array $params) //phpcs:ignore
    {
        $this->getSystemEventManager()->queueNotifyProjectStatusChange(
            $params['group_id'],
            Project::STATUS_SUSPENDED
        );
    }

    public function project_is_active(array $params) //phpcs:ignore
    {
        $this->getSystemEventManager()->queueNotifyProjectStatusChange(
            $params['group_id'],
            Project::STATUS_ACTIVE
        );
    }

    public function project_is_deleted(array $params) //phpcs:ignore
    {
        $this->getSystemEventManager()->queueNotifyProjectStatusChange(
            $params['group_id'],
            Project::STATUS_DELETED
        );
    }

    private function getSystemEventManager()
    {
        return new ProjectOwnershipSystemEventManager(
            SystemEventManager::instance()
        );
    }
}
