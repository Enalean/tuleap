<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Service;

use Project;
use ServiceManager;
use Service;
use SvnPlugin;
use Tuleap\Service\ServiceCreator;
use Tuleap\SVN\ServiceSvn;

class ServiceActivator
{

    /**
     * @var ServiceManager
     */
    private $service_manager;

    /**
     * @var ServiceCreator
     */
    private $service_creator;

    public function __construct(ServiceManager $service_manager, ServiceCreator $service_creator)
    {
        $this->service_manager = $service_manager;
        $this->service_creator = $service_creator;
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

        if ($svn_core_service) {
            $data->unsetProjectServiceUsage($svn_core_service->getId());

            if ($svn_plugin_service && $this->atLeastOneSVNServiceIsUsedInTemplate($svn_core_service, $svn_plugin_service)) {
                $data->forceServiceUsage($svn_plugin_service->getId());
            }

            $params['use_legacy_services'][Service::SVN] = false;
        }
    }

    public function forceUsageOfService(Project $project, Project $template, array $legacy)
    {
        $project_services       = $this->service_manager->getListOfAllowedServicesForProject($project);
        $new_svn_plugin_service = $this->getSVNPluginService($project_services);

        if ($new_svn_plugin_service || $legacy[Service::SVN] === true) {
            return true;
        }

        $template_services   = $this->service_manager->getListOfAllowedServicesForProject($template);
        $svn_plugin_service  = $this->getSVNPluginService($template_services);
        $svn_core_service    = $this->getSVNCoreService($template_services);

        if ($svn_core_service || $svn_plugin_service) {
            return $this->service_creator->createService(
                ServiceSvn::getDefaultServiceData($project->getID()),
                $project->getID(),
                [
                    'system' => $template->isSystem(),
                    'name'   => $template->isSystem() ? '' : $template->getUnixName(),
                    'id'     => $template->getID(),
                    'is_used'   => (int) $this->mustServiceBeUsed($svn_core_service, $svn_plugin_service),
                    'is_active' => (int) $this->mustServiceBeActive($svn_core_service, $svn_plugin_service),
                ]
            );
        }
    }

    private function mustServiceBeActive(
        ?Service $svn_core_service = null,
        ?Service $svn_plugin_service = null
    ) {
        return (bool) (($svn_core_service && $svn_core_service->isActive()) ||
            ($svn_plugin_service && $svn_plugin_service->isActive()));
    }

    private function mustServiceBeUsed(
        ?Service $svn_core_service = null,
        ?Service $svn_plugin_service = null
    ) {
        return (bool) (($svn_core_service && $svn_core_service->isUsed()) ||
            ($svn_plugin_service && $svn_plugin_service->isUsed()));
    }

    private function atLeastOneSVNServiceIsUsedInTemplate(Service $svn_core_service, Service $svn_plugin_service)
    {
        return $svn_core_service->isUsed() || $svn_plugin_service->isUsed();
    }

    /**
     * @return Service
     */
    private function getSVNCoreService(array $template_services)
    {
        foreach ($template_services as $service) {
            if ($service->getShortName() === Service::SVN) {
                return $service;
            }
        }
    }

    /**
     * @return Service
     */
    private function getSVNPluginService(array $template_services)
    {
        foreach ($template_services as $service) {
            if ($service->getShortName() === SvnPlugin::SERVICE_SHORTNAME) {
                return $service;
            }
        }
    }
}
