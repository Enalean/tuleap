<?php
/**
 * Copyright Enalean (c) 2019. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\Timetracking\REST\v1;

use Tracker;
use Tuleap\Project\REST\MinimalProjectRepresentation;
use Tuleap\REST\JsonCast;

class TimetrackingTrackerReportRepresentation
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $label;

    /**
     * @var MinimalProjectRepresentation
     */
    public $project;

    /**
     * @var TimetrackingTrackerUserRepresentation[]
     */
    public $time_per_user;

    public function build(Tracker $tracker, array $time_per_user): void
    {
        $this->id    = JsonCast::toInt($tracker->getId());
        $this->uri   = $tracker->getUri();
        $this->label = $tracker->getName();

        $project_reference = new MinimalProjectRepresentation();
        $project_reference->buildMinimal($tracker->getProject());
        $this->project = $project_reference;

        $this->time_per_user = $time_per_user;
    }
}
