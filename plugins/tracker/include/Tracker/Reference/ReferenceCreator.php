<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Reference;

use Project;
use ReferenceDao;
use Service;
use ServiceManager;
use trackerPlugin;
use TrackerV3;

class ReferenceCreator
{
    /**
     * @var ServiceManager
     */
    private $service_manager;

    /**
     * @var TrackerV3
     */
    private $tracker_v3;

    /**
     * @var ReferenceDao
     */
    private $reference_dao;

    public function __construct(
        ServiceManager $service_manager,
        TrackerV3 $tracker_v3,
        ReferenceDao $reference_dao
    ) {
        $this->service_manager = $service_manager;
        $this->tracker_v3      = $tracker_v3;
        $this->reference_dao   = $reference_dao;
    }

    public function insertArtifactsReferencesFromLegacy(Project $project)
    {
        if (! $this->tracker_v3->available()) {
            return;
        }

        $project_services           = $this->service_manager->getListOfAllowedServicesForProject($project);
        $new_tracker_plugin_service = $this->getTrackerPluginService($project_services);

        if (! $new_tracker_plugin_service) {
            return;
        }

        $this->insertReference($project, 'art');
        $this->insertReference($project, 'artifact');
    }

    private function insertReference(Project $project, $keyword)
    {
        $reference = $this->reference_dao->getSystemReferenceByNatureAndKeyword(
            $keyword,
            TrackerV3::REFERENCE_NATURE
        );

        if ($reference) {
            $this->reference_dao->create_ref_group(
                $reference['id'],
                $project->usesService(trackerPlugin::SERVICE_SHORTNAME),
                $project->getID()
            );
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
