<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

use Tuleap\Artidoc\ArtidocController;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Request\DispatchableWithRequest;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../docman/vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ArtidocPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-artidoc', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-artidoc', 'Artidoc'),
                    dgettext('tuleap-artidoc', 'Artifacts as Documents'),
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker', 'docman'];
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/artidoc', function (FastRoute\RouteCollector $r) {
            $r->get('/{project_name:[A-z0-9-]+}/', $this->getRouteHandler('routeController'));
        });
    }

    public function routeController(): DispatchableWithRequest
    {
        return new ArtidocController(
            ProjectManager::instance(),
            $this,
        );
    }
}
