<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\REST;

use Tracker;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class TrackerReference
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
     * @var ProjectReference {@type Tuleap\Tracker\REST\ProjectReference} {@required false}
     */
    public $project;

    private function __construct(int $id, string $label, \Project $project)
    {
        $this->id    = $id;
        $this->uri   = CompleteTrackerRepresentation::ROUTE . '/' . $id;
        $this->label = $label;

        $project_reference = new ProjectReference($project);
        $this->project = $project_reference;
    }

    public static function build(Tracker $tracker): self
    {
        return new self(
            JsonCast::toInt($tracker->getId()),
            $tracker->getName(),
            $tracker->getProject(),
        );
    }
}
