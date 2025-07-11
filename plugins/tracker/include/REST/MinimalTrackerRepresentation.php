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

use Tuleap\Project\REST\ProjectReference;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Semantic\CollectionOfCannotCreateArtifactReason;
use Tuleap\Tracker\Tracker;

/**
 * @psalm-immutable
 */
class MinimalTrackerRepresentation implements TrackerRepresentation
{
    public const MINIMAL_REPRESENTATION = 'minimal';

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

    /**
     * @var string[]|null
     */
    public ?array $cannot_create_reasons;

    private function __construct(int $id, string $uri, string $label, string $color_name, ProjectReference $project, ?array $cannot_create_reasons)
    {
        $this->id                    = $id;
        $this->uri                   = $uri;
        $this->label                 = $label;
        $this->color_name            = $color_name;
        $this->project               = $project;
        $this->cannot_create_reasons = $cannot_create_reasons;
    }

    public static function build(Tracker $tracker): self
    {
        $tracker_id = $tracker->getId();
        return new self(
            JsonCast::toInt($tracker_id),
            CompleteTrackerRepresentation::ROUTE . '/' . $tracker_id,
            $tracker->getName(),
            $tracker->getColor()->value,
            new ProjectReference($tracker->getProject()),
            null
        );
    }

    public static function withCannotCreateArtifactReasons(Tracker $tracker, CollectionOfCannotCreateArtifactReason $reasons): self
    {
        $tracker_id = $tracker->getId();
        return new self(
            JsonCast::toInt($tracker_id),
            CompleteTrackerRepresentation::ROUTE . '/' . $tracker_id,
            $tracker->getName(),
            $tracker->getColor()->value,
            new ProjectReference($tracker->getProject()),
            $reasons->toStringArray()
        );
    }
}
