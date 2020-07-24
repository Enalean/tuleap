<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Service;

use ServiceManager;
use ServiceTracker;
use Service;
use trackerPlugin;
use TrackerV3;
use Project;
use Tuleap\Service\ServiceCreator;

class ServiceActivator
{

    /**
     * @var ServiceCreator
     */
    private $service_creator;

    /**
     * @var ServiceManager
     */
    private $service_manager;

    /**
     * @var TrackerV3
     */
    private $tracker_v3;

    public function __construct(ServiceManager $service_manager, TrackerV3 $tracker_v3, ServiceCreator $service_creator)
    {
        $this->service_manager = $service_manager;
        $this->tracker_v3      = $tracker_v3;
        $this->service_creator = $service_creator;
    }

    public function unuseLegacyService(array &$params)
    {
        $template = $params['template'];
        $data     = $params['project_creation_data'];

        if (! $data->projectShouldInheritFromTemplate()) {
            return;
        }

        if (! $this->tracker_v3->available()) {
            $params['use_legacy_services'][Service::TRACKERV3] = false;
            return;
        }

        $template_services      = $this->service_manager->getListOfAllowedServicesForProject($template);
        $tracker_core_service   = $this->getTrackerCoreService($template_services);
        $tracker_plugin_service = $this->getTrackerPluginService($template_services);

        if ($tracker_core_service) {
            $data->unsetProjectServiceUsage($tracker_core_service->getId());

            if ($tracker_plugin_service && $this->atLeastOneTrackerServiceIsUsedInTemplate($tracker_core_service, $tracker_plugin_service)) {
                $data->forceServiceUsage($tracker_plugin_service->getId());
            }

            $params['use_legacy_services'][Service::TRACKERV3] = false;
        }
    }

    public function forceUsageOfService(Project $project, Project $template, array $legacy)
    {
        $project_services           = $this->service_manager->getListOfAllowedServicesForProject($project);
        $new_tracker_plugin_service = $this->getTrackerPluginService($project_services);

        if ($new_tracker_plugin_service || $legacy[Service::TRACKERV3] === true) {
            return;
        }

        $template_services      = $this->service_manager->getListOfAllowedServicesForProject($template);
        $tracker_plugin_service = $this->getTrackerPluginService($template_services);
        $tracker_core_service   = $this->getTrackerCoreService($template_services);

        if ($tracker_core_service || $tracker_plugin_service) {
            $this->service_creator->createService(
                ServiceTracker::getDefaultServiceData($project->getID()),
                $project->getID(),
                [
                    'system' => $template->isSystem(),
                    'name'   => $template->isSystem() ? '' : $template->getUnixName(),
                    'id'     => $template->getID(),
                    'is_used'   => (int) $this->mustServiceBeUsed($tracker_core_service, $tracker_plugin_service),
                    'is_active' => (int) $this->mustServiceBeActive($tracker_core_service, $tracker_plugin_service),
                ]
            );
        }
    }

    private function mustServiceBeActive(
        ?Service $tracker_core_service = null,
        ?Service $tracker_plugin_service = null
    ) {
        return (bool) (($tracker_core_service && $tracker_core_service->isActive()) ||
            ($tracker_plugin_service && $tracker_plugin_service->isActive()));
    }

    private function mustServiceBeUsed(
        ?Service $tracker_core_service = null,
        ?Service $tracker_plugin_service = null
    ) {
        return (bool) (($tracker_core_service && $tracker_core_service->isUsed()) ||
            ($tracker_plugin_service && $tracker_plugin_service->isUsed()));
    }

    private function atLeastOneTrackerServiceIsUsedInTemplate(Service $tracker_core_service, Service $tracker_plugin_service)
    {
        return $tracker_core_service->isUsed() || $tracker_plugin_service->isUsed();
    }

    /**
     * @return Service
     */
    private function getTrackerCoreService(array $template_services)
    {
        foreach ($template_services as $service) {
            if ($service->getShortName() === Service::TRACKERV3) {
                return $service;
            }
        }
    }

    /**
     * @return Service
     */
    private function getTrackerPluginService(array $template_services)
    {
        foreach ($template_services as $service) {
            if ($service->getShortName() === trackerPlugin::SERVICE_SHORTNAME) {
                return $service;
            }
        }
    }
}
