<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Tracker_REST_Artifact_ArtifactRepresentationBuilder {
    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory) {
        $this->formelement_factory = $formelement_factory;
    }

    public function getArtifactRepresentation(PFUser $user, Tracker_Artifact $artifact) {
        return new Tracker_REST_Artifact_ArtifactRepresentation(
            $artifact,
            $this->getFieldsValues($user, $artifact)
        );
    }

    private function getFieldsValues(PFUser $user, Tracker_Artifact $artifact) {
        $changeset = $artifact->getLastChangeset();
        return array_values(
            array_filter(
                array_map(
                    $this->getFieldsValuesFilter($user, $changeset),
                    $this->formelement_factory->getUsedFieldsForSoap($artifact->getTracker())
                )
            )
        );
    }

    private function getFieldsValuesFilter(PFUser $user, Tracker_Artifact_Changeset $changeset) {
        return function (Tracker_FormElement_Field $field) use ($user, $changeset) {
            if ($field->userCanRead($user)) {
                return $field->getRESTValue($user, $changeset);
            }
            return false;
        };
    }
}
