<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use Tuleap\Baseline\ServiceController;
use Tuleap\Layout\ServiceUrlCollector;

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../../tracker/include/trackerPlugin.class.php';

class baselinePlugin extends Plugin  // @codingStandardsIgnoreLine
{
    public const NAME = 'baseline';
    public const SERVICE_SHORTNAME = 'plugin_baseline';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-baseline', __DIR__.'/../site-content');
    }

    public function getDependencies() : array
    {
        return ['tracker'];
    }

    public function getServiceShortname() : string
    {
        return self::SERVICE_SHORTNAME;
    }

    public function getHooksAndCallbacks() : Collection
    {
        $this->addHook(\Tuleap\Request\CollectRoutesEvent::NAME);

        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);

        return parent::getHooksAndCallbacks();
    }

    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Baseline\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector)
    {
        if ($collector->getServiceShortname() === $this->getServiceShortname()) {
            $collector->setUrl($this->getPluginPath() . "/" . urlencode($collector->getProject()->getUnixNameLowerCase()));
        }
    }

    public function routeGetSlash() : ServiceController
    {
        return new ServiceController(
            ProjectManager::instance(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . "/../templates"),
            $this
        );
    }

    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event) : void
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (FastRoute\RouteCollector $r) {
            $r->get('/{project_name}[/]', $this->getRouteHandler('routeGetSlash'));
        });
    }
}
