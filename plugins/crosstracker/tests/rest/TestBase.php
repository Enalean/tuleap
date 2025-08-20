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

declare(strict_types=1);

namespace Tuleap\CrossTracker;

use Tuleap\REST\RestBase;

class TestBase extends RestBase
{
    public const string REVERSE_CROSS_TRACKER_SHORTNAME = 'myCrossLink';
    public const string FORWARD_CROSS_TRACKER_SHORTNAME = 'epic';
    public const int WIDGET_ID                          = 5;

    protected int $reverse_cross_tracker_project_id;
    protected int $forward_cross_tracker_project_id;
    protected int $reverse_cross_tracker_tracker_id;

    /** @var int[] */
    protected array $reverse_artifact_cross_tracker_ids = [];
    protected int $forward_cross_tracker_tracker_id;
    /** @var int[] */
    protected array $forward_artifact_cross_tracker_ids = [];

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->reverse_cross_tracker_project_id = $this->getProjectId(CrossTrackerDataBuilder::REVERSE_CROSS_PROJECT_SHORTNAME);
        $this->forward_cross_tracker_project_id = $this->getProjectId(CrossTrackerDataBuilder::FORWARD_CROSS_PROJECT_SHORTNAME);
        $this->reverse_cross_tracker_tracker_id = $this->tracker_ids[$this->reverse_cross_tracker_project_id][self::REVERSE_CROSS_TRACKER_SHORTNAME];
        $this->forward_cross_tracker_tracker_id = $this->tracker_ids[$this->forward_cross_tracker_project_id][self::FORWARD_CROSS_TRACKER_SHORTNAME];

        $this->getArtifactIds(
            $this->reverse_cross_tracker_tracker_id,
            $this->reverse_artifact_cross_tracker_ids
        );
        $this->getArtifactIds(
            $this->forward_cross_tracker_tracker_id,
            $this->forward_artifact_cross_tracker_ids
        );
    }
}
