<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Tests\REST;

use RestBase;

class TrackerBase extends RestBase
{
    const MOVE_PROJECT_NAME         = 'move-artifact';
    const DELETE_PROJECT_NAME       = 'test-delete-artifacts';
    const MOVE_TRACKER_SHORTNAME    = 'ToMoveArtifacts';
    const BASE_TRACKER_SHORTNAME    = 'base';
    const DELETE_TRACKER_SHORTNAME  = 'diasabled_delete_artifacts_testing_2';

    protected $delete_tracker_id;
    protected $move_tracker_id;
    protected $base_tracker_id;

    protected $base_artifact_ids   = [];
    protected $delete_artifact_ids = [];

    public function setUp()
    {
        parent::setUp();

        $move_project_id   = $this->getProjectId(self::MOVE_PROJECT_NAME);
        $delete_project_id = $this->getProjectId(self::DELETE_PROJECT_NAME);

        $this->move_tracker_id   = $this->tracker_ids[$move_project_id][self::MOVE_TRACKER_SHORTNAME];
        $this->base_tracker_id   = $this->tracker_ids[$move_project_id][self::BASE_TRACKER_SHORTNAME];
        $this->delete_tracker_id = $this->tracker_ids[$delete_project_id][self::DELETE_TRACKER_SHORTNAME];

        $this->getBaseArtifactIds();
        $this->getDeleteArtifactIds();
    }

    private function getBaseArtifactIds()
    {
        $this->getArtifactIds(
            $this->base_tracker_id,
            $this->base_artifact_ids
        );
    }

    private function getDeleteArtifactIds()
    {
        $this->getArtifactIds(
            $this->delete_tracker_id,
            $this->delete_artifact_ids
        );
    }
}
