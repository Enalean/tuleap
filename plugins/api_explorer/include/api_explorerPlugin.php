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

use Tuleap\Request\CollectRoutesEvent;
use Tuleap\REST\ExplorerEndpointAvailableEvent;

require_once __DIR__ . '/../vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class api_explorerPlugin extends Plugin
{
    private const API_EXPLORER_ENDPOINT = '/api/explorer/';

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
        $event->getRouteCollector()->get(
            self::API_EXPLORER_ENDPOINT,
            $this->getRouteHandler('routeGet')
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

    public function explorerEndpointAvailableEvent(ExplorerEndpointAvailableEvent $event): void
    {
        $event->enableExplorer(self::API_EXPLORER_ENDPOINT);
    }
}
