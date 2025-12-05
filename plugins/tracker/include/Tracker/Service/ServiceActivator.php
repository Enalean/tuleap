<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

    public function __construct(ServiceManager $service_manager, ServiceCreator $service_creator)
    {
        $this->service_manager = $service_manager;
        $this->service_creator = $service_creator;
    }

    public function forceUsageOfService(Project $project, Project $template)
    {
        $project_services           = $this->service_manager->getListOfAllowedServicesForProject($project);
        $new_tracker_plugin_service = $this->getTrackerPluginService($project_services);

        if ($new_tracker_plugin_service) {
            return;
        }

        $template_services      = $this->service_manager->getListOfAllowedServicesForProject($template);
        $tracker_plugin_service = $this->getTrackerPluginService($template_services);

        if ($tracker_plugin_service) {
            $this->service_creator->createService(
                ServiceTracker::getDefaultServiceData($project->getID()),
                $project->getID(),
                [
                    'system' => $template->isSystem(),
                    'name'   => $template->isSystem() ? '' : $template->getUnixName(),
                    'id'     => $template->getID(),
                    'is_used'   => (int) $this->mustServiceBeUsed($tracker_plugin_service),
                    'is_active' => (int) $this->mustServiceBeActive($tracker_plugin_service),
                ]
            );
        }
    }

    private function mustServiceBeActive(
        ?Service $tracker_plugin_service = null,
    ) {
        return $tracker_plugin_service && $tracker_plugin_service->isActive();
    }

    private function mustServiceBeUsed(
        ?Service $tracker_plugin_service = null,
    ) {
        return $tracker_plugin_service && $tracker_plugin_service->isUsed();
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
