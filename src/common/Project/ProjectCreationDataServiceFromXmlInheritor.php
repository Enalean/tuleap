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

namespace Tuleap\Project;

use Project;
use Service;
use ServiceManager;
use SimpleXMLElement;

class ProjectCreationDataServiceFromXmlInheritor
{
    /**
     * @var ServiceManager
     */
    private $service_manager;

    public function __construct(ServiceManager $service_manager)
    {
        $this->service_manager = $service_manager;
    }

    /**
     * Read the template and XML and mark services as being in use if they are
     * allowed in the template and enabled in the XML.
     */
    public function markUsedServicesFromXML(
        SimpleXMLElement $xml,
        Project $template
    ): array {
        $data_services = [];

        $services_by_name = [];
        foreach ($this->service_manager->getListOfAllowedServicesForProject($template) as $template_service) {
            $services_by_name[$template_service->getShortName()] = $template_service;
        }

        foreach ($xml->services->children() as $service) {
            if (! ($service instanceof SimpleXMLElement)) {
                continue;
            }
            if ($service->getName() !== "service") {
                continue;
            }
            $attrs = $service->attributes();

            if (! isset($attrs['shortname']) || ! isset($attrs['enabled'])) {
                continue;
            }

            $name = (string) $attrs['shortname'];

            $enabled = \Tuleap\XML\PHPCast::toBoolean($attrs['enabled']);
            if (isset($services_by_name[$name])) {
                $service_id                 = $services_by_name[$name]->getId();
                $data_services[$service_id] = ['is_used' => $enabled];
            }
        }

        return $this->forceAdminServiceUsage($services_by_name, $data_services);
    }

    protected function forceAdminServiceUsage(array $services_by_name, array $data_services): array
    {
        $service_id                 = $services_by_name[Service::ADMIN]->getId();
        $data_services[$service_id] = ['is_used' => true];

        return $data_services;
    }
}
