<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker;

use RestBase;

class TestBase extends RestBase
{
    public const string CROSS_TRACKER_SHORTNAME = 'myCrossLink';
    public const WIDGET_ID                      = 4;

    protected int $cross_tracker_project_id;
    protected int $cross_tracker_tracker_id;

    /** @var int[] */
    protected array $artifact_cross_tracker_ids = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->cross_tracker_project_id = (int) $this->getProjectId(CrossTrackerDataBuilder::CROSS_PROJECT_SHORTNAME);
        $this->cross_tracker_tracker_id = (int) $this->tracker_ids[$this->cross_tracker_project_id][self::CROSS_TRACKER_SHORTNAME];

        $this->getArtifactIds(
            $this->cross_tracker_tracker_id,
            $this->artifact_cross_tracker_ids
        );
    }
}
