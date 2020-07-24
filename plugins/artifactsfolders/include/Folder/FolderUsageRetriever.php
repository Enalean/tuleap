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

namespace Tuleap\ArtifactsFolders\Folder;

use Project;
use TrackerFactory;
use PFUser;

class FolderUsageRetriever
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;

    /**
     * @var Dao
     */
    private $dao;

    public function __construct(Dao $dao, TrackerFactory $tracker_factory)
    {
        $this->dao             = $dao;
        $this->tracker_factory = $tracker_factory;
    }

    public function projectUsesArtifactsFolders(Project $project, PFUser $user)
    {
        $project_tracker     = $this->tracker_factory->getTrackersByGroupIdUserCanView($project->getID(), $user);
        $project_tracker_ids = $this->getProjectTrackerIds($project_tracker);

        return $this->dao->projectUsesArtifactsFolders($project_tracker_ids);
    }

    /**
     * @return array
     */
    private function getProjectTrackerIds(array $project_tracker)
    {
        $project_tracker_ids = [];
        foreach ($project_tracker as $tracker) {
            $project_tracker_ids[] = $tracker->getId();
        }

        return $project_tracker_ids;
    }

    public function doesProjectHaveAFolderTracker(Project $project)
    {
        $project_tracker     = $this->tracker_factory->getTrackersByGroupId($project->getID());
        $project_tracker_ids = $this->getProjectTrackerIds($project_tracker);

        return $this->dao->projectUsesArtifactsFolders($project_tracker_ids);
    }
}
