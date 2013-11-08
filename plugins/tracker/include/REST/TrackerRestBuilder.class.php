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

class Tracker_REST_TrackerRestBuilder {
    /** @var Tracker_FormElementFactory */
    private $formelement_factory;

    public function __construct(Tracker_FormElementFactory $formelement_factory) {
        $this->formelement_factory = $formelement_factory;
    }

    public function getTrackerRepresentation(PFUser $user, Tracker $tracker) {
        $semantic_manager = new Tracker_SemanticManager($tracker);

        return new Tracker_REST_TrackerRepresentation(
            $tracker,
            $this->getRESTFieldsUserCanRead($user, $tracker),
            $semantic_manager->exportToREST($user),
            $tracker->getWorkflow()->exportToREST($user)
        );
    }

    private function getRESTFieldsUserCanRead(PFUser $user, Tracker $tracker) {
        return
            array_filter(
                array_map(
                    $this->getRESTFieldUserCanReadFilter($user),
                    $this->formelement_factory->getUsedFields($tracker)
                )
            );
    }

    private function getRESTFieldUserCanReadFilter(PFUser $user) {
        $formelement_factory = $this->formelement_factory;

        return function (Tracker_FormElement_Field $field) use ($user, $formelement_factory) {
            if ($field->userCanRead($user)) {
                return new Tracker_REST_FieldRepresentation(
                    $field,
                    $formelement_factory->getType($field),
                    $field->exportCurrentUserPermissionsToSOAP($user)
                );
            }
            return false;
        };
    }
}