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

namespace Tuleap\TestManagement\REST\v1;

use Tracker_Artifact;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

class BugRepresentation
{
    /**
     * @var int ID of the artifact
     */
    public $id;

    /**
     * @var String
     */
    public $xref;

    /**
     * @var String
     */
    public $title;

    /**
     * @var MinimalTrackerRepresentation
     */
    public $tracker;

    public function build(Tracker_Artifact $bug_artifact, MinimalTrackerRepresentation $tracker_representation): void
    {
        $this->id      = JsonCast::toInt($bug_artifact->getId());
        $this->xref    = $bug_artifact->getXRef();
        $this->title   = $bug_artifact->getTitle() ?? '';
        $this->tracker = $tracker_representation;
    }
}
