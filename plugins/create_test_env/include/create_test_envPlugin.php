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
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\CreateTestEnv\ActivitiesAnalytics\ListActivitiesController;
use Tuleap\CreateTestEnv\NotificationBotDao;
use Tuleap\CreateTestEnv\NotificationBotIndexController;
use Tuleap\CreateTestEnv\NotificationBotSaveController;
use Tuleap\CreateTestEnv\REST\ResourcesInjector as CreateTestEnvResourcesInjector;
use Tuleap\CreateTestEnv\Plugin\PluginInfo;
use Tuleap\Project\ServiceAccessEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\CreateTestEnv\Notifier;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\Tracker\Artifact\Event\ArtifactUpdated;
use Tuleap\User\User_ForgeUserGroupPermissionsFactory;
use Tuleap\User\UserAuthenticationSucceeded;
use Tuleap\User\UserConnectionUpdateEvent;
use Tuleap\Admin\AdminPageRenderer;

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
        return ['botmattermost', 'tracker'];
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook('site_admin_option_hook');

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

    public function restResources(array $params)
    {
        $create_test_env_injector = new CreateTestEnvResourcesInjector();
        $create_test_env_injector->populate($params['restler']);
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function routeGetNotificationBot(): NotificationBotIndexController
    {
        require_once __DIR__ . '/../../botmattermost/include/botmattermostPlugin.php';

        return new NotificationBotIndexController(
            new BotFactory(new BotDao()),
            new NotificationBotDao(),
            new AdminPageRenderer()
        );
    }

    public function routePostNotificationBot(): NotificationBotSaveController
    {
        require_once __DIR__ . '/../../botmattermost/include/botmattermostPlugin.php';

        return new NotificationBotSaveController(
            new NotificationBotDao(),
            $this->getPluginPath()
        );
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

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (FastRoute\RouteCollector $r) {
            $r->get('/notification-bot', $this->getRouteHandler('routeGetNotificationBot'));
            $r->post('/notification-bot', $this->getRouteHandler('routePostNotificationBot'));

            $r->get('/daily-activities', $this->getRouteHandler('routeGetActivities'));
            $r->get('/weekly-summary', $this->getRouteHandler('routeGetWeeklySummary'));
        });
    }

    // @codingStandardsIgnoreLine
    public function site_admin_option_hook(array &$params)
    {
        $params['plugins'][] = [
            'label' => dgettext('tuleap-create_test_env', 'Create test environment'),
            'href'  => $this->getPluginPath() . '/notification-bot'
        ];
    }

    public function trackerArtifactCreated(ArtifactCreated $event)
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact     = $event->getArtifact();
        $project      = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', "Created artifact #" . $artifact->getId());
    }

    public function trackerArtifactUpdated(ArtifactUpdated $event)
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact     = $event->getArtifact();
        $project      = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', "Updated artifact #" . $artifact->getId());
    }

    public function userAuthenticationSucceeded(UserAuthenticationSucceeded $event)
    {
        $platform_url = HTTPRequest::instance()->getServerUrl();
        $current_user = $event->getUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $this->notify("[{$current_user->getRealName()}](mailto:{$current_user->getEmail()}) logged in $platform_url. #connection #{$current_user->getUnixName()}");
        (new ActivityLoggerDao())->insert($current_user->getId(), 0, 'platform', 'Login');
    }

    public function userConnectionUpdateEvent(UserConnectionUpdateEvent $event)
    {
        $platform_url = HTTPRequest::instance()->getServerUrl();
        $current_user = $event->getUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $this->notify("[{$current_user->getRealName()}](mailto:{$current_user->getEmail()}) is using $platform_url. #connection #{$current_user->getUnixName()}");
        (new ActivityLoggerDao())->insert($current_user->getId(), 0, 'platform', 'Connexion');
    }

    // @codingStandardsIgnoreLine
    public function service_is_used(array $params)
    {
        $request = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $platform_url = $request->getServerUrl();
        $project = ProjectManager::instance()->getProject($params['group_id']);
        $verb = $params['is_used'] ? 'activated' : 'desactivated';
        $this->notify("[{$current_user->getRealName()}](mailto:{$current_user->getEmail()}) $verb service {$params['shortname']} in [{$project->getPublicName()}]({$platform_url}/project/{$project->getID()}/admin/services. #project-admin #{$current_user->getUnixName()}");
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'project_admin', "$verb service {$params['shortname']}");
    }

    public function serviceAccessEvent(ServiceAccessEvent $event)
    {
        $request = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $project = $request->getProject();
        $project_id = 0;
        if ($project && ! $project->isError()) {
            $project_id = $project->getID();
        }
        (new ActivityLoggerDao())->insert($current_user->getId(), $project_id, $event->getServiceName(), "Access");
    }

    private function notify($text)
    {
        (new Notifier(new NotificationBotDao()))->notify($text);
    }

    // @codingStandardsIgnoreLine
    public function codendi_daily_start()
    {
        $emails = $this->getDailyActivityNotificationEmails();
        if (count($emails) === 0) {
            return;
        }
        $dao = new ActivityLoggerDao();
        $now = new DateTimeImmutable();
        $yesterday = $now->sub(new DateInterval('P1DT30M'));
        $csv_handle = fopen('php://temp', 'w+');
        fputcsv($csv_handle, ['user_id', 'login', 'email', 'service', 'action', 'time']);
        foreach ($dao->fetchActivityBetweenDates($yesterday->getTimestamp(), $now->getTimestamp()) as $row) {
            fputcsv($csv_handle, $row);
        }
        rewind($csv_handle);

        $zip_file_name = tempnam(ForgeConfig::get('codendi_cache_dir'), 'create_test_env_daily_zip_');
        try {
            $date_tag = $now->format('Y-m-d');
            $zip = new ZipArchive();
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
