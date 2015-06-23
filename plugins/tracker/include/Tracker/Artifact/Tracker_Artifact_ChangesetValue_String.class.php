<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Manage values in changeset for string fields
 */
class Tracker_Artifact_ChangesetValue_String extends Tracker_Artifact_ChangesetValue_Text {

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor) {
        return $visitor->visitString($this);
    }

    /**
     * @see Tracker_Artifact_ChangesetValue_Text::fetchHtmlMailDiff()
     */
    protected function fetchHtmlMailDiff($formated_diff, $artifact_id, $changeset_id) {
        return $formated_diff;
    }

    /**
     * @see Tracker_Artifact_ChangesetValue_Text::fetchDiffInFollowUp()
     */
    protected function fetchDiffInFollowUp($formated_diff) {
        return '<div class="diff">'. $formated_diff .'</div>';
    }

    protected function getFullRESTRepresentation($value) {
        $classname_with_namespace = 'Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation';

        $artifact_field_value_full_representation = new $classname_with_namespace;
        $artifact_field_value_full_representation->build(
            $this->field->getId(),
            Tracker_FormElementFactory::instance()->getType($this->field),
            $this->field->getLabel(),
            $value
        );

        return $artifact_field_value_full_representation;
    }
}