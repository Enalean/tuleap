<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Svn\Service;

use ServiceManager;
use Service;
use SvnPlugin;

class ServiceActivator
{

    /**
     * @var ServiceManager
     */
    private $service_manager;

    public function __construct(ServiceManager $service_manager)
    {
        $this->service_manager = $service_manager;
    }

    public function unuseLegacyService(array &$params)
    {
        $template = $params['template'];
        $data     = $params['project_creation_data'];

        if (! $data->projectShouldInheritFromTemplate()) {
            return;
        }

        $template_services   = $this->service_manager->getListOfAllowedServicesForProject($template);
        $svn_core_service    = $this->getSVNCoreService($template_services);
        $svn_plugin_service  = $this->getSVNPluginService($template_services);

        if ($svn_core_service && $svn_plugin_service) {
            $data->unsetProjectServiceUsage($svn_core_service->getId());

            if ($this->atLeastOneSVNServiceIsUsedInTemplate($svn_core_service, $svn_plugin_service)) {
                $data->forceServiceUsage($svn_plugin_service->getId());
            }

            $params['use_legacy_services'][Service::SVN] = false;
        }
    }

    private function atLeastOneSVNServiceIsUsedInTemplate(Service $svn_core_service, Service $svn_plugin_service)
    {
        return $svn_core_service->isUsed() || $svn_plugin_service->isUsed();
    }

    private function getSVNCoreService(array $template_services)
    {
        foreach ($template_services as $service) {
            if ($service->getShortName() === Service::SVN) {
                return $service;
            }
        }
    }

    private function getSVNPluginService(array $template_services)
    {
        foreach ($template_services as $service) {
            if ($service->getShortName() === SvnPlugin::SERVICE_SHORTNAME) {
                return $service;
            }
        }
    }
}
