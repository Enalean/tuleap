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

namespace Tuleap\Trafficlights\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use Project;
use Tracker_ArtifactFactory;
use Tracker_ReportFactory;
use Tracker_URLVerification;
use Tuleap\REST\ProjectAuthorization;
use Tuleap\Trafficlights\ArtifactFactory;
use Tuleap\Trafficlights\Config;

class DefinitionSelector
{
    const ALL = 'all';

    const MILESTONE = 'milestone';

    const NONE = 'none';

    const REPORT = 'report';

    /** @var Config */
    private $config;

    /** @var ArtifactFactory */
    private $artifact_factory;

    /** @var ProjectAuthorization */
    private $project_authorization;

    /** Tracker_ArtifactFactory */
    private $tracker_artifact_factory;

    /** @var Tracker_ReportFactory */
    private $tracker_report_factory;

    public function __construct(
        Config $config,
        ArtifactFactory $artifact_factory,
        ProjectAuthorization $project_authorization,
        Tracker_ArtifactFactory $tracker_artifact_factory,
        Tracker_ReportFactory $tracker_report_factory
    ) {
        $this->config                   = $config;
        $this->artifact_factory         = $artifact_factory;
        $this->project_authorization    = $project_authorization;
        $this->tracker_artifact_factory = $tracker_artifact_factory;
        $this->tracker_report_factory   = $tracker_report_factory;
    }

    public function selectDefinitionIds(PFUser $user, Project $project, $selector, $milestone_id = 0, $report_id = 0)
    {
        switch ($selector) {
            case self::ALL:
                $definition_ids = $this->selectAllDefinitionIds($user, $project);
                break;

            case self::MILESTONE:
                $definition_ids = $this->selectDefinitionIdsFromMilestone($user, $project, $milestone_id);
                break;

            case self::REPORT:
                $definition_ids = $this->selectDefinitionIdsFromReport($user, $report_id);
                break;

            default:
                $definition_ids = $this->selectNoDefinitionIds();
                break;
        }
        return $definition_ids;
    }

    public function selectNoDefinitionIds()
    {
        return array();
    }

    public function selectAllDefinitionIds(PFUser $user, Project $project)
    {
        $tracker_id = $this->config->getTestDefinitionTrackerId($project);
        $artifacts  = $this->tracker_artifact_factory->getArtifactsByTrackerIdUserCanView(
            $user,
            $tracker_id
        );
        return $this->getDefinitionIds($artifacts);
    }

    public function selectDefinitionIdsFromMilestone(PFUser $user, Project $project, $milestone_id)
    {
        $cover_definitions = $this->artifact_factory->getCoverTestDefinitionsUserCanViewForMilestone(
            $user,
            $project,
            $milestone_id
        );
        return $this->getDefinitionIds($cover_definitions);
    }

    public function selectDefinitionIdsFromReport(PFUser $user, $report_id)
    {
        $report       = $this->getReportById($user, $report_id);
        $matching_ids = $report->getMatchingIds();

        if (! $matching_ids['id']) {
            return array();
        }
        return explode(',', $matching_ids['id']);
    }

    private function getDefinitionIds($definitions)
    {
        $ids = array();
        foreach ($definitions as $definition) {
            $ids[] = $definition->getId();
        }
        return $ids;
    }

    private function getReportById(PFUser $user, $id)
    {
        $store_in_session = false;
        $report = $this->tracker_report_factory->getReportById(
            $id,
            $user->getId(),
            $store_in_session
        );

        if (! $report) {
            throw new RestException(404);
        }

        $tracker = $report->getTracker();
        if (! $tracker->userCanView($user)) {
            throw new RestException(403);
        }

        $this->project_authorization->userCanAccessProject(
            $user,
            $tracker->getProject(),
            new Tracker_URLVerification()
        );

        return $report;
    }
}
