<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

use Tuleap\Baseline\Domain\BaselineArtifact;
use Tuleap\REST\JsonCast;

class BaselineArtifactRepresentation
{
    /** @var int */
    public $id;

    /** @var string|null */
    public $title;

    /** @var string|null */
    public $description;

    /** @var string|null */
    public $status;

    /** @var int */
    public $tracker_id;

    /** @var string */
    public $tracker_name;

    /** @var int[] */
    public $linked_artifact_ids;

    private function __construct(
        int $id,
        ?string $title,
        ?string $description,
        ?string $status,
        int $tracker_id,
        string $tracker_name,
        array $linked_artifact_ids,
    ) {
        $this->id                  = $id;
        $this->title               = $title;
        $this->description         = $description;
        $this->status              = $status;
        $this->tracker_id          = $tracker_id;
        $this->tracker_name        = $tracker_name;
        $this->linked_artifact_ids = $linked_artifact_ids;
    }

    public static function fromArtifact(BaselineArtifact $artifact): BaselineArtifactRepresentation
    {
        return new self(
            JsonCast::toInt($artifact->getId()),
            $artifact->getTitle(),
            $artifact->getDescription(),
            $artifact->getStatus(),
            JsonCast::toInt($artifact->getTrackerId()),
            $artifact->getTrackerName(),
            JsonCast::toArrayOfInts($artifact->getLinkedArtifactIds())
        );
    }
}
