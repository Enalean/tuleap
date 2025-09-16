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

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Tuleap\CreateTestEnv\ActivitiesAnalytics\DisplayUserActivities;
use Tuleap\CreateTestEnv\ActivitiesAnalytics\WeeklySummaryController;
use Tuleap\CreateTestEnv\ActivityLogger\ActivityLoggerDao;
use Tuleap\CreateTestEnv\ActivitiesAnalytics\ListActivitiesController;
use Tuleap\CreateTestEnv\REST\ResourcesInjector as CreateTestEnvResourcesInjector;
use Tuleap\Project\ServiceAccessEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;
use Tuleap\User\UserAuthenticationSucceeded;
use Tuleap\User\UserConnectionUpdateEvent;

// @codingStandardsIgnoreLine
class create_test_envPlugin extends Plugin
{
    public const string NAME = 'create_test_env';

    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-create_test_env', __DIR__ . '/../site-content');
    }

    #[\Override]
    public function getPluginInfo(): \PluginInfo
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \PluginInfo($this);
            $this->pluginInfo->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-create_test_env', 'Create test environment'),
                    dgettext('tuleap-create_test_env', 'Automate creation of test users and environments')
                )
            );
        }

        return $this->pluginInfo;
    }

    #[\Override]
    public function getDependencies()
    {
        return ['tracker'];
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::REST_RESOURCES)]
    public function restResources(array $params): void
    {
        $create_test_env_injector = new CreateTestEnvResourcesInjector();
        $create_test_env_injector->populate($params['restler']);
    }

    public function routeGetActivities(): DispatchableWithRequest
    {
        return new ListActivitiesController(
            TemplateRendererFactory::build(),
            new ActivityLoggerDao(),
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            ),
        );
    }

    public function routeGetWeeklySummary(): DispatchableWithRequest
    {
        return new WeeklySummaryController(
            TemplateRendererFactory::build(),
            new ActivityLoggerDao(),
            new User_ForgeUserGroupPermissionsManager(
                new User_ForgeUserGroupPermissionsDao()
            ),
        );
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (FastRoute\RouteCollector $r) {
            $r->get('/daily-activities', $this->getRouteHandler('routeGetActivities'));
            $r->get('/weekly-summary', $this->getRouteHandler('routeGetWeeklySummary'));
        });
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerArtifactCreated(ArtifactCreated $event): void
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact = $event->getArtifact();
        $project  = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', 'Created artifact #' . $artifact->getId());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function trackerArtifactUpdated(ArtifactUpdated $event): void
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact = $event->getArtifact();
        $project  = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', 'Updated artifact #' . $artifact->getId());
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
    {
        $current_user = $event->user;
        if ($current_user->isSuperUser()) {
            return;
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), 0, 'platform', 'Login');
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function userConnectionUpdateEvent(UserConnectionUpdateEvent $event): void
    {
        $current_user = $event->getUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), 0, 'platform', 'Connexion');
    }

    #[\Tuleap\Plugin\ListeningToEventName(Event::SERVICE_IS_USED)]
    public function serviceIsUsed(array $params): void
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $project = ProjectManager::instance()->getProject($params['group_id']);
        $verb    = $params['is_used'] ? 'activated' : 'desactivated';
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'project_admin', "$verb service {$params['shortname']}");
    }

    #[\Tuleap\Plugin\ListeningToEventClass]
    public function serviceAccessEvent(ServiceAccessEvent $event): void
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $project    = $request->getProject();
        $project_id = 0;
        if ($project && ! $project->isError()) {
            $project_id = $project->getID();
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), $project_id, $event->getServiceName(), 'Access');
    }

    #[\Tuleap\Plugin\ListeningToEventName('codendi_daily_start')]
    public function codendiDailyStart(): void
    {
        $one_year_ago = (new DateTimeImmutable())->sub(new DateInterval('P1Y'));
        $dao          = new ActivityLoggerDao();
        $dao->purgeOldData($one_year_ago->getTimestamp());
    }

    #[\Tuleap\Plugin\ListeningToEventName(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION)]
    public function getPermissionDelegation(array &$params): void
    {
        $params['plugins_permission'][DisplayUserActivities::ID] = new DisplayUserActivities();
    }
}
