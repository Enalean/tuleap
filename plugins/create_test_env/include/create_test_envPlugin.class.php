<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../vendor/autoload.php';

use Tuleap\CreateTestEnv\ActivityLogger\ActivityLoggerDao;
use Tuleap\Layout\IncludeAssets;
use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\CallMeBack\CallMeBackEmailDao;
use Tuleap\CallMeBack\CallMeBackMessageDao;
use Tuleap\CallMeBack\CallMeBackAdminController;
use Tuleap\CallMeBack\CallMeBackAdminSaveController;
use Tuleap\CreateTestEnv\NotificationBotDao;
use Tuleap\CreateTestEnv\NotificationBotIndexController;
use Tuleap\CreateTestEnv\NotificationBotSaveController;
use Tuleap\CreateTestEnv\REST\ResourcesInjector as CreateTestEnvResourcesInjector;
use Tuleap\CallMeBack\REST\ResourcesInjector as CallMeBackResourcesInjector;
use Tuleap\CreateTestEnv\Plugin\PluginInfo;
use Tuleap\Project\ServiceAccessEvent;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\CreateTestEnv\Notifier;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
use Tuleap\User\UserAuthenticationSucceeded;
use Tuleap\User\UserConnectionUpdateEvent;
use Tuleap\Admin\AdminPageRenderer;

// @codingStandardsIgnoreLine
class create_test_envPlugin extends Plugin
{
    const NAME = 'create_test_env';

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
        if (!$this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getDependencies()
    {
        return [ 'botmattermost', 'tracker' ];
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook('cssfile');
        $this->addHook('javascript_file');
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook('site_admin_option_hook');

        $this->addHook(UserAuthenticationSucceeded::NAME);
        $this->addHook(UserConnectionUpdateEvent::NAME);
        $this->addHook(Event::SERVICE_IS_USED);
        $this->addHook(ArtifactCreated::NAME);
        $this->addHook(TRACKER_EVENT_ARTIFACT_POST_UPDATE);
        $this->addHook(ServiceAccessEvent::NAME);

        $this->addHook('codendi_daily_start');

        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);

        return parent::getHooksAndCallbacks();
    }

    public function restResources(array $params)
    {
        $create_test_env_injector = new CreateTestEnvResourcesInjector();
        $create_test_env_injector->populate($params['restler']);

        $call_me_back_injector = new CallMeBackResourcesInjector();
        $call_me_back_injector->populate($params['restler']);
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->get($this->getPluginPath() . '/notification-bot', function () {
            return new NotificationBotIndexController(
                new BotFactory(new BotDao()),
                new NotificationBotDao(),
                new AdminPageRenderer()
            );
        });
        $event->getRouteCollector()->post($this->getPluginPath() . '/notification-bot', function () {
            return new NotificationBotSaveController(
                new NotificationBotDao(),
                $this->getPluginPath()
            );
        });

        $event->getRouteCollector()->get($this->getPluginPath() . '/call-me-back', function () {
            return new CallMeBackAdminController(
                new CallMeBackEmailDao(),
                new CallMeBackMessageDao(),
                new AdminPageRenderer()
            );
        });
        $event->getRouteCollector()->post($this->getPluginPath() . '/call-me-back', function () {
            return new CallMeBackAdminSaveController(
                new CallMeBackEmailDao(),
                new CallMeBackMessageDao()
            );
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
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', "Created artifact #".$artifact->getId());
    }

    // @codingStandardsIgnoreLine
    public function tracker_event_artifact_post_update(array $params)
    {
        $request      = HTTPRequest::instance();
        $current_user = $request->getCurrentUser();
        if ($current_user->isSuperUser()) {
            return;
        }
        $artifact     = $params['artifact'];
        $project      = $artifact->getTracker()->getProject();
        (new ActivityLoggerDao())->insert($current_user->getId(), $project->getID(), 'tracker', "Updated artifact #".$artifact->getId());
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
        $this->notify("[{$current_user->getRealName()}](mailto:{$current_user->getEmail()}) $verb service {$params['shortname']} in [{$project->getUnconvertedPublicName()}]({$platform_url}/project/admin/servicebar.php?group_id={$project->getID()}). #project-admin #{$current_user->getUnixName()}");
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
            $mail->setSubject("[create_test_env] Activity snapshot at ".$now->format('c'));
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

    public function cssfile($params)
    {
        if ($this->shouldCallMeBackButtonBeDisplayed()) {
            $assets = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/create_test_env/FlamingParrot',
                '/assets/create_test_env/FlamingParrot'
            );
            $css_file_url = $assets->getFileURL('style.css');
            echo '<link rel="stylesheet" type="text/css" href="' . $css_file_url . '" />';
        }
    }

    // @codingStandardsIgnoreLine
    public function javascript_file()
    {
        if ($this->shouldCallMeBackButtonBeDisplayed()) {
            $assets = new IncludeAssets(
                __DIR__ . '/../../../src/www/assets/create_test_env/scripts',
                '/assets/create_test_env/scripts'
            );

            echo $assets->getHTMLSnippet('call-me-back-flaming-parrot.js') . PHP_EOL;
        }
    }


    public function burningParrotGetStylesheets(array $params)
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/create_test_env/BurningParrot',
            '/assets/create_test_env/BurningParrot'
        );

        $variant = $params['variant'];
        $params['stylesheets'][] = $assets->getFileURL('create-test-env-' . $variant->getName() . '.css');
    }

    public function burningParrotGetJavascriptFiles(array $params)
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/create_test_env/scripts',
            '/assets/create_test_env/scripts'
        );

        if ($this->shouldCallMeBackButtonBeDisplayed()) {
            $params['javascript_files'][] = $assets->getFileURL('call-me-back-burning-parrot.js');
        }

        if (strpos($_SERVER['REQUEST_URI'], '/plugins/create_test_env/call-me-back') === 0) {
            $params['javascript_files'][] = '/scripts/ckeditor-4.3.2/ckeditor.js';
            $params['javascript_files'][] = '/scripts/tuleap/tuleap-ckeditor-toolbar.js';
            $params['javascript_files'][] = $assets->getFileURL('call-me-back-admin.js');
        }
    }

    private function shouldCallMeBackButtonBeDisplayed()
    {
        $current_user = UserManager::instance()->getCurrentUser();

        return $current_user->isLoggedIn()
            && $current_user->getPreference('plugin_call_me_back_asked_to_be_called_back') !== '1';
    }
}
