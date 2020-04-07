<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\Project\XML;

use Tuleap\Event\Dispatchable;

class ServiceEnableForXmlImportRetriever implements Dispatchable
{
    public const NAME = 'serviceEnableForXmlImportRetriever';

    /**
     * @var array
     */
    private $available_services = [];
    /**
     * @var \PluginFactory
     */
    private $plugin_factory;

    public function __construct(\PluginFactory $plugin_factory)
    {
        $this->plugin_factory = $plugin_factory;
        $this->plugin_factory->getAvailablePlugins();
    }

    public function addServiceByName(string $service_name): void
    {
        $this->available_services[$service_name] = true;
    }

    public function addServiceIfPluginIsNotRestricted(\Plugin $plugin, string $service_name): void
    {
        if ($this->plugin_factory->isProjectPluginRestricted($plugin)) {
            return;
        }
        $this->available_services[$service_name] = true;
    }

    public function getAvailableServices(): array
    {
        return $this->available_services;
    }
}
