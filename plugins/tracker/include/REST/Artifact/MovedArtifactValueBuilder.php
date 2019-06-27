<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Tracker;
use Luracast\Restler\RestException;
use Tracker_Artifact;
use Tuleap\Tracker\Exception\SemanticTitleNotDefinedException;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class MovedArtifactValueBuilder
{
    /** @return  ArtifactValuesRepresentation[] */
    public function getValues(Tracker_Artifact $artifact, Tracker $tracker_destination)
    {
        $this->checkSemantic($artifact, $tracker_destination);

        $representation           = new ArtifactValuesRepresentation();
        $representation->field_id = (int) $tracker_destination->getTitleField()->getId();
        $representation->value    = $artifact->getTitle();

        return array($representation);
    }

    private function checkSemantic(Tracker_Artifact $artifact, Tracker $tracker)
    {
        if (! $artifact->getTitle()) {
            throw new SemanticTitleNotDefinedException("No semantic found for title artifact");
        }

        if (! $tracker->getTitleField()) {
            throw new SemanticTitleNotDefinedException("No title semantic found in tracker dest");
        }
    }
}
