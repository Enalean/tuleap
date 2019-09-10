<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Tracker;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;

class MinimalTrackerRepresentation implements TrackerRepresentation
{
    /**
     * @var int ID of the tracker {@type int} {@required true}
     */
    public $id;

    /**
     * @var string URI of the tracker {@type string} {@required false}
     */
    public $uri;

    /**
     * @var string Display Name of the tracker {@type string} {@required false}
     */
    public $label;

    /**
     * @var string TLP normalized color name {@type string} {@required false}
     */
    public $color_name;

    /**
     * @var ProjectReference {@type Tuleap\Tracker\REST\ProjectReference} {@required false}
     */
    public $project;

    public function build(Tracker $tracker): self
    {
        $this->id         = JsonCast::toInt($tracker->getId());
        $this->uri        = CompleteTrackerRepresentation::ROUTE . '/' . $this->id;
        $this->label      = $tracker->getName();
        $this->color_name = $tracker->getColor()->getName();

        $project       = $tracker->getProject();
        $this->project = new ProjectReference();
        $this->project->build($project);

        return $this;
    }
}
