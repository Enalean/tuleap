<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\TestManagement\XML;

use PFUser;
use SimpleXMLElement;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategy;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;

class TrackerArtifactXMLImportXMLImportFieldStrategySteps implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy
{
    public function getFieldData(
        Tracker_FormElement_Field $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Artifact $artifact,
        PostCreationContext $context,
    ): array {
        $data = [];
        foreach ($field_change->step as $step) {
            $data['description_format'][]      = (string) $step->description->attributes()['format'];
            $data['description'][]             = (string) $step->description;
            $data['expected_results_format'][] = (string) $step->expected_results->attributes()['format'];
            $data['expected_results'][]        = (string) $step->expected_results;
        }

        return $data;
    }
}
