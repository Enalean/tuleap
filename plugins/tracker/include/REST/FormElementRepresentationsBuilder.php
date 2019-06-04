<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use PFUser;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElement;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElementFactory;
use Tracker_REST_FieldOpenListRepresentation;
use Tracker_REST_FormElementDateRepresentation;
use Tracker_REST_FormElementRepresentation;

class FormElementRepresentationsBuilder
{
    /**
     * @var PermissionsExporter
     */
    private $permissions_exporter;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        PermissionsExporter $permissions_exporter
    ) {
        $this->permissions_exporter = $permissions_exporter;
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @return Tracker_REST_FormElementRepresentation[]
     */
    public function buildRepresentationsInTrackerContext(Tracker $tracker, PFUser $user) : array
    {
        return $this->buildRepresentations($tracker, null, $user);
    }

    /**
     * @return Tracker_REST_FormElementRepresentation[]
     */
    public function buildRepresentationsInArtifactContext(Tracker_Artifact $artifact, PFUser $user) : array
    {
        return $this->buildRepresentations($artifact->getTracker(), $artifact, $user);
    }

    /**
     * @return Tracker_REST_FormElementRepresentation[]
     */
    private function buildRepresentations(Tracker $tracker, ?Tracker_Artifact $artifact, PFUser $user) : array
    {
        $representation_collection = [];
        foreach ($this->form_element_factory->getAllUsedFormElementOfAnyTypesForTracker($tracker) as $form_element) {
            if (! $form_element->userCanRead($user)) {
                continue;
            }

            if ($form_element instanceof Tracker_FormElement_Field_Date) {
                $form_element_representation = new Tracker_REST_FormElementDateRepresentation();
            } elseif ($form_element instanceof Tracker_FormElement_Field_OpenList) {
                $form_element_representation = new Tracker_REST_FieldOpenListRepresentation();
            } else {
                $form_element_representation = new Tracker_REST_FormElementRepresentation();
            }

            $form_element_representation->build(
                $form_element,
                $this->form_element_factory->getType($form_element),
                $this->getPermissionsForFormElement($form_element, $artifact, $user)
            );

            $representation_collection[] = $form_element_representation;
        }

        return $representation_collection;
    }

    private function getPermissionsForFormElement(
        Tracker_FormElement $form_element,
        ?Tracker_Artifact $artifact,
        PFUser $user
    ) : array {
        if ($artifact === null) {
            return $this->permissions_exporter->exportUserPermissionsForFieldWithoutWorkflowComputedPermissions(
                $user,
                $form_element
            );
        }

        return $this->permissions_exporter->exportUserPermissionsForFieldWithWorkflowComputedPermissions(
            $user,
            $form_element,
            $artifact
        );
    }
}
