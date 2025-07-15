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

use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\REST\ProjectReference;
use Tuleap\Tracker\Tracker;

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
     * @var string Color of the tracker {@type string} {@required false}
     */
    public string $color;

    /**
     * @var ProjectReference {@required false}
     */
    public $project;

    private function __construct(Tracker $tracker, ProjectReference $project)
    {
        $this->id    = $tracker->getId();
        $this->uri   = CompleteTrackerRepresentation::ROUTE . '/' . $this->id;
        $this->label = $tracker->getName();
        $this->color = $tracker->getColor()->value;

        $this->project = $project;
    }

    public static function build(Tracker $tracker): self
    {
        return new self(
            $tracker,
            new ProjectReference($tracker->getProject()),
        );
    }

    public static function buildWithExtendedProjectReference(Tracker $tracker, ProjectBackgroundConfiguration $project_background_configuration): self
    {
        return new self(
            $tracker,
            ProjectReferenceWithBackground::fromProject($tracker->getProject(), $project_background_configuration),
        );
    }
}
