<?php
/**
 * Copyright (c) Enalean, 2015-Present. All rights reserved
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

namespace Tuleap\Tracker\REST\Artifact;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\MinimalTrackerRepresentation;

/**
 * @psalm-immutable
 */
final class ParentArtifactRepresentation
{
    private function __construct(
        public int $id,
        public string $title,
        public string $xref,
        public string $uri,
        public string $html_url,
        public MinimalTrackerRepresentation $tracker,
        public ?StatusValueRepresentation $full_status,
    ) {
    }

    public static function build(Artifact $artifact, ?StatusValueRepresentation $status_value_representation): ParentArtifactRepresentation
    {
        $artifact_id = $artifact->getId();
        return new self(
            $artifact_id,
            $artifact->getCachedTitle() ?? '',
            $artifact->getXRef(),
            ArtifactRepresentation::ROUTE . '/' . $artifact_id,
            $artifact->getUri(),
            MinimalTrackerRepresentation::build($artifact->getTracker()),
            $status_value_representation
        );
    }
}
