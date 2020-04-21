<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

declare(strict_types=1);

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\APIExplorer\Specification\Swagger\SwaggerJsonController;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\REST\ExplorerEndpointAvailableEvent;
use Tuleap\REST\ResourcesInjector;
use Tuleap\REST\RestlerFactory;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class api_explorerPlugin extends Plugin
{
    private const API_EXPLORER_ENDPOINT       = '/api/explorer/';
    public const SERVICE_NAME_INSTRUMENTATION = 'api-explorer';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);
        bindtextdomain('tuleap-api_explorer', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-api_explorer', 'API Explorer'),
                    '',
                    dgettext('tuleap-api_explorer', 'Web API Explorer')
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks(): Collection
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(\Tuleap\REST\ExplorerEndpointAvailableEvent::NAME);
        return parent::getHooksAndCallbacks();
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $route_collector = $event->getRouteCollector();
        $route_collector->get(
            self::API_EXPLORER_ENDPOINT,
            $this->getRouteHandler('routeGet')
        );
        $route_collector->get(
            self::API_EXPLORER_ENDPOINT . 'swagger.json',
            $this->getRouteHandler('routeGetSwaggerJson')
        );
    }

    public function routeGet(): \Tuleap\APIExplorer\ExplorerController
    {
        return new \Tuleap\APIExplorer\ExplorerController(
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates/'),
            new \Tuleap\Layout\IncludeAssets(
                __DIR__ . '/../../../src/www/assets/api-explorer',
                '/assets/api-explorer'
            )
        );
    }

    public function routeGetSwaggerJson(): SwaggerJsonController
    {
        $event_manager = EventManager::instance();
        return new SwaggerJsonController(
            new RestlerFactory(
                new RestlerCache(),
                new ResourcesInjector(),
                $event_manager,
            ),
            VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence())->version_identifier,
            $event_manager,
            Codendi_HTMLPurifier::instance(),
            new JSONResponseBuilder(HTTPFactoryBuilder::responseFactory(), HTTPFactoryBuilder::streamFactory()),
            new SapiEmitter(),
            new ServiceInstrumentationMiddleware(self::SERVICE_NAME_INSTRUMENTATION)
        );
    }

    public function explorerEndpointAvailableEvent(ExplorerEndpointAvailableEvent $event): void
    {
        $event->enableExplorer(self::API_EXPLORER_ENDPOINT);
    }
}
