<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Project\REST\ProjectRepresentation;
use Tuleap\Project\REST\ProjectResourceReference;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\ReportRepresentation;
use Tuleap\Tracker\REST\Artifact\UsersArtifactsResource;
use Tuleap\Tracker\REST\v1\ArtifactFilesResource;
use Tuleap\Tracker\REST\v1\ArtifactsResource;
use Tuleap\Tracker\REST\v1\ArtifactTemporaryFilesResource;
use Tuleap\Tracker\REST\v1\ProjectTrackersResource;
use Tuleap\Tracker\REST\v1\ReportsResource;
use Tuleap\Tracker\REST\v1\TrackerFieldsResource;
use Tuleap\Tracker\REST\v1\TrackersResource;
use Tuleap\Tracker\REST\v1\Workflow\TransitionsResource;
use Tuleap\User\REST\UserRepresentation;

/**
 * Inject resource into restler
 */
class Tracker_REST_ResourcesInjector
{
    public function populate(Luracast\Restler\Restler $restler)
    {
        $restler->addAPIClass(ProjectTrackersResource::class, ProjectRepresentation::ROUTE);
        $restler->addAPIClass(TrackersResource::class, 'trackers');
        $restler->addAPIClass(ArtifactsResource::class, 'artifacts');
        $restler->addAPIClass(ArtifactFilesResource::class, 'artifact_files');
        $restler->addAPIClass(ArtifactTemporaryFilesResource::class, 'artifact_temporary_files');
        $restler->addAPIClass(ReportsResource::class, ReportRepresentation::ROUTE);
        $restler->addAPIClass(TrackerFieldsResource::class, TrackerFieldsResource::ROUTE);
        $restler->addAPIClass(TransitionsResource::class, 'tracker_workflow_transitions');
        $restler->addAPIClass(UsersArtifactsResource::class, UserRepresentation::ROUTE);
    }

    public function declareProjectPlanningResource(array &$resources, Project $project)
    {
        $resource_reference = new ProjectResourceReference();
        $resource_reference->build($project, CompleteTrackerRepresentation::ROUTE);

        $resources[] = $resource_reference;
    }
}
