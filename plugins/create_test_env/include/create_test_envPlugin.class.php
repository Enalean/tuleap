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

use Tuleap\BotMattermost\Bot\BotDao;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\CreateTestEnv\NotificationBotDao;
use Tuleap\CreateTestEnv\NotificationBotIndexController;
use Tuleap\CreateTestEnv\NotificationBotSaveController;
use Tuleap\CreateTestEnv\REST\ResourcesInjector;
use Tuleap\CreateTestEnv\Plugin\PluginInfo;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\BurningParrotCompatiblePageEvent;
use Tuleap\CreateTestEnv\Notifier;
use Tuleap\Tracker\Artifact\Event\ArtifactCreated;
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
        return ['botmattermost'];
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(BurningParrotCompatiblePageEvent::NAME);
        $this->addHook('site_admin_option_hook');
        $this->addHook(UserConnectionUpdateEvent::NAME);

        return parent::getHooksAndCallbacks();
    }

    public function restResources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    public function burningParrotCompatiblePage(BurningParrotCompatiblePageEvent $event)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $event->setIsInBurningParrotCompatiblePage();
        }
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->get($this->getPluginPath(), function () {
            return new NotificationBotIndexController(
                new BotFactory(new BotDao()),
                new NotificationBotDao(),
                new AdminPageRenderer(),
                TemplateRendererFactory::build()->getRenderer(__DIR__.'/../templates')
            );
        });
        $event->getRouteCollector()->post($this->getPluginPath(), function () {
            return new NotificationBotSaveController(
                new NotificationBotDao(),
                $this->getPluginPath()
            );
        });
    }

    // @codingStandardsIgnoreLine
    public function site_admin_option_hook(array &$params)
    {
        $params['plugins'][] = [
            'label' => dgettext('tuleap-create_test_env', 'Create test environment'),
            'href'  => $this->getPluginPath()
        ];
    }

    public function userConnectionUpdateEvent(UserConnectionUpdateEvent $event)
    {
        $platform_url = HTTPRequest::instance()->getServerUrl();
        $current_user = $event->getUser();
        $this->notify("[{$current_user->getRealName()}](mailto:{$current_user->getEmail()}) is using $platform_url. #connection #{$current_user->getUnixName()}");
    }

    private function notify($text)
    {
        (new Notifier(new NotificationBotDao()))->notify($text);
    }
}
