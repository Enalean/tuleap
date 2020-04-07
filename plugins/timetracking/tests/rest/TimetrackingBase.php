<?php
/**
 * Copyright (c) Enalean, 2018. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Timetracking\REST;

use RestBase;

class TimetrackingBase extends RestBase
{

    public const PROJECT_NAME = 'test-timetracking';
    public const TRACKER_NAME = 'timetracking_testing';

    protected $tracker_timetracking;
    protected $timetracking_project_id;
    protected $timetracking_artifact_ids;
    protected $timetracking_user_test;

    public function setUp(): void
    {
        parent::setUp();
        $this->timetracking_project_id   = $this->getProjectId(self::PROJECT_NAME);
        $this->tracker_timetracking      = $this->tracker_ids[$this->timetracking_project_id][self::TRACKER_NAME];
        $this->timetracking_artifact_ids = $this->getArtifacts($this->tracker_timetracking);
        $this->timetracking_user_test    = $this->initUserId(TimetrackingDataBuilder::USER_TESTER_NAME);
    }
}
