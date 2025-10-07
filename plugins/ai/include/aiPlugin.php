<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Admin\RejectNonSiteAdministratorMiddleware;
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
use Tuleap\AI\Mistral\MistralConnector;
use Tuleap\AI\SiteAdmin\AISiteAdminController;
use Tuleap\AI\SiteAdmin\AISiteAdminUpdateSettingsController;
use Tuleap\Config\ConfigClassProvider;
use Tuleap\Config\ConfigDao;
use Tuleap\Config\ConfigSet;
use Tuleap\Config\GetConfigKeys;
use Tuleap\Config\PluginWithConfigKeys;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\FeedbackSerializer;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../vendor/autoload.php';

final class aiPlugin extends Plugin implements PluginWithConfigKeys // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-ai', __DIR__ . '/../site-content');
    }

    #[Override]
    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-ai', 'AI'),
                    dgettext('tuleap-ai', 'Global AI provider, connect to external AI services.'),
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    #[\Override]
    public function getConfigKeys(ConfigClassProvider $event): void
    {
        $event->addConfigClass(MistralConnector::class);
    }

    #[ListeningToEventClass]
    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build(
                dgettext('tuleap-ai', 'AI Connectors'),
                AISiteAdminController::ADMIN_SETTINGS_URL
            )
        );
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->get(
            AISiteAdminController::ADMIN_SETTINGS_URL,
            $this->getRouteHandler('routeGetAdminSettings'),
        );
        $event->getRouteCollector()->post(
            AISiteAdminController::ADMIN_SETTINGS_URL,
            $this->getRouteHandler('routePostAdminSettings'),
        );
    }

    public function routeGetAdminSettings(): AISiteAdminController
    {
        return new AISiteAdminController(
            new AdminPageRenderer(),
            UserManager::instance(),
        );
    }

    public function routePostAdminSettings(): AISiteAdminUpdateSettingsController
    {
        $config_keys = EventManager::instance()->dispatch(new GetConfigKeys());
        assert($config_keys instanceof GetConfigKeys);

        return new AISiteAdminUpdateSettingsController(
            AISiteAdminController::getCSRFToken(),
            new ConfigSet($config_keys, new ConfigDao()),
            new RedirectWithFeedbackFactory(HTTPFactoryBuilder::responseFactory(), new FeedbackSerializer(new FeedbackDao())),
            new SapiEmitter(),
            new RejectNonSiteAdministratorMiddleware(UserManager::instance()),
        );
    }
}
