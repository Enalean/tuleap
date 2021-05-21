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
use Tuleap\CreateTestEnv\Plugin\PluginInfo;
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
    public const NAME = 'create_test_env';

    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-create_test_env', __DIR__ . '/../site-content');
    }

    /**
     * @return Tuleap\CreateTestEnv\Plugin\PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return ['tracker'];
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(CollectRoutesEvent::NAME);

        $this->addHook(UserAuthenticationSucceeded::NAME);
        $this->addHook(UserConnectionUpdateEvent::NAME);
        $this->addHook(Event::SERVICE_IS_USED);
        $this->addHook(ArtifactCreated::NAME);
        $this->addHook(ArtifactUpdated::NAME);
        $this->addHook(ServiceAccessEvent::NAME);

        $this->addHook(User_ForgeUserGroupPermissionsFactory::GET_PERMISSION_DELEGATION);

        $this->addHook('codendi_daily_start');

        return parent::getHooksAndCallbacks();
    }

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

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (FastRoute\RouteCollector $r) {
            $r->get('/daily-activities', $this->getRouteHandler('routeGetActivities'));
            $r->get('/weekly-summary', $this->getRouteHandler('routeGetWeeklySummary'));
        });
    }

    public function trackerArtifactCreated(ArtifactCreated $event): void
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact = $event->getArtifact();
        $project  = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', "Created artifact #" . $artifact->getId());
    }

    public function trackerArtifactUpdated(ArtifactUpdated $event): void
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact = $event->getArtifact();
        $project  = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', "Updated artifact #" . $artifact->getId());
    }

    public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event): void
    {
        $current_user = $event->user;
        if ($current_user->isSuperUser()) {
            return;
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), 0, 'platform', 'Login');
    }

    public function userConnectionUpdateEvent(UserConnectionUpdateEvent $event): void
    {
        $current_user = $event->getUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), 0, 'platform', 'Connexion');
    }

    // @codingStandardsIgnoreLine
    public function service_is_used(array $params): void
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

    public function serviceAccessEvent(ServiceAccessEvent $event)
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
        (new ActivityLoggerDao())->insert($current_user->getId(), $project_id, $event->getServiceName(), "Access");
    }

    // @codingStandardsIgnoreLine
    public function codendi_daily_start()
    {
        $emails = $this->getDailyActivityNotificationEmails();
        if (count($emails) === 0) {
            return;
        }
        $dao        = new ActivityLoggerDao();
        $now        = new DateTimeImmutable();
        $yesterday  = $now->sub(new DateInterval('P1DT30M'));
        $csv_handle = fopen('php://temp', 'w+');
        fputcsv($csv_handle, ['user_id', 'login', 'email', 'service', 'action', 'time']);
        foreach ($dao->fetchActivityBetweenDates($yesterday->getTimestamp(), $now->getTimestamp()) as $row) {
            fputcsv($csv_handle, $row);
        }
        rewind($csv_handle);

        $zip_file_name = tempnam(ForgeConfig::get('codendi_cache_dir'), 'create_test_env_daily_zip_');
        try {
            $date_tag = $now->format('Y-m-d');
            $zip      = new ZipArchive();
            $zip->open($zip_file_name, ZipArchive::CREATE);
            $zip->addFromString("csv-export-$date_tag.csv", stream_get_contents($csv_handle));
            $zip->close();
            fclose($csv_handle);

            $mail = new Codendi_Mail();
            $mail->setTo(implode(',', $emails));
            $mail->setSubject("[create_test_env] Activity snapshot at " . $now->format('c'));
            $mail->addAttachment(file_get_contents($zip_file_name), 'application/zip', "csv-export-$date_tag.zip");
            $mail->send();
        } finally {
            unlink($zip_file_name);
            $one_year_ago = $now->sub(new DateInterval('P1Y'));
            $dao->purgeOldData($one_year_ago->getTimestamp());
        }
    }

    private function getDailyActivityNotificationEmails()
    {
        $str_value = $this->getPluginInfo()->getPropertyValueForName('create_test_env_daily_snapshot_email');
        return array_filter(array_map('trim', explode(',', $str_value)));
    }

    public function get_permission_delegation(array &$params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['plugins_permission'][DisplayUserActivities::ID] = new DisplayUserActivities();
    }
}
