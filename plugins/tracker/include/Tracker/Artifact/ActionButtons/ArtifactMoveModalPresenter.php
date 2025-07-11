<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\ActionButtons;

use Tuleap\Tracker\Artifact\Artifact;

class ArtifactMoveModalPresenter
{
    /**
     * @var int
     */
    public $tracker_id;
    /**
     * @var string
     */
    public $tracker_name;
    /**
     * @var string
     */
    public $tracker_color;
    /**
     * @var int
     */
    public $artifact_id;
    /**
     * @var int
     */
    public $project_id;

    public function __construct(Artifact $artifact)
    {
        $tracker             = $artifact->getTracker();
        $this->tracker_id    = $artifact->getTrackerId();
        $this->tracker_name  = $tracker->getItemName();
        $this->tracker_color = $tracker->getColor()->value;
        $this->artifact_id   = $artifact->getId();
        $this->project_id    = (int) $tracker->getProject()->getID();
    }
}
