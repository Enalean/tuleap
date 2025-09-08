<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\XMLImport;

use PFUser;
use SimpleXMLElement;
use Tracker_Artifact_XMLImport_XMLImportFieldStrategy;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedField;
use Tuleap\Tracker\FormElement\Field\TrackerField;

class XMLImportFieldStrategyComputed implements Tracker_Artifact_XMLImport_XMLImportFieldStrategy
{
    #[\Override]
    public function getFieldData(
        TrackerField $field,
        SimpleXMLElement $field_change,
        PFUser $submitted_by,
        Artifact $artifact,
        PostCreationContext $context,
    ) {
        $computed_value = [];

        if (isset($field_change->manual_value)) {
            $computed_value[ComputedField::FIELD_VALUE_MANUAL] = (string) $field_change->manual_value;
        }
        if (isset($field_change->is_autocomputed)) {
            $computed_value[ComputedField::FIELD_VALUE_IS_AUTOCOMPUTED] = (string) $field_change->is_autocomputed;
        }

        return $computed_value;
    }
}
