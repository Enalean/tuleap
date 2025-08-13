<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
use Tracker_FormElement;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_OpenList;
use Tracker_FormElementFactory;
use Tracker_REST_FormElement_FieldDateRepresentation;
use Tracker_REST_FormElement_FieldOpenListRepresentation;
use Tracker_REST_FormElementRepresentation;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\IRetrieveAllUsableTypesInProject;
use Tuleap\Tracker\FormElement\Field\Date\DateField;
use Tuleap\Tracker\REST\FormElement\FieldFileRepresentation;
use Tuleap\Tracker\REST\FormElement\LinksFieldRepresentation;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\Tracker;

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

    /**
     * @var HiddenFieldsetChecker
     */
    private $hidden_fieldset_checker;
    /**
     * @var PermissionsForGroupsBuilder
     */
    private $permissions_for_groups_builder;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        PermissionsExporter $permissions_exporter,
        HiddenFieldsetChecker $hidden_fieldset_checker,
        PermissionsForGroupsBuilder $permissions_for_groups_builder,
        private IRetrieveAllUsableTypesInProject $types_allowed_retriever,
    ) {
        $this->permissions_exporter           = $permissions_exporter;
        $this->form_element_factory           = $form_element_factory;
        $this->hidden_fieldset_checker        = $hidden_fieldset_checker;
        $this->permissions_for_groups_builder = $permissions_for_groups_builder;
    }

    /**
     * @return Tracker_REST_FormElementRepresentation[]
     */
    public function buildRepresentationsInTrackerContext(Tracker $tracker, PFUser $user): array
    {
        return $this->buildRepresentations($tracker, null, $user);
    }

    /**
     * @return Tracker_REST_FormElementRepresentation[]
     */
    public function buildRepresentationsInArtifactContext(Artifact $artifact, PFUser $user): array
    {
        return $this->buildRepresentations($artifact->getTracker(), $artifact, $user);
    }

    /**
     * @return Tracker_REST_FormElementRepresentation[]
     */
    private function buildRepresentations(Tracker $tracker, ?Artifact $artifact, PFUser $user): array
    {
        $representation_collection = [];
        foreach ($this->form_element_factory->getAllUsedFormElementOfAnyTypesForTracker($tracker) as $form_element) {
            if (! $form_element->userCanRead($user)) {
                continue;
            }

            if ($form_element instanceof Tracker_FormElement_Field_File) {
                $form_element_representation = FieldFileRepresentation::build(
                    $form_element,
                    $this->form_element_factory->getType($form_element),
                    $this->getPermissionsForFormElement($form_element, $artifact, $user),
                    $this->permissions_for_groups_builder->getPermissionsForGroups($form_element, $artifact, $user)
                );
            } elseif ($form_element instanceof DateField) {
                $form_element_representation = Tracker_REST_FormElement_FieldDateRepresentation::build(
                    $form_element,
                    $this->form_element_factory->getType($form_element),
                    $this->getPermissionsForFormElement($form_element, $artifact, $user),
                    $this->permissions_for_groups_builder->getPermissionsForGroups($form_element, $artifact, $user)
                );
            } elseif ($form_element instanceof Tracker_FormElement_Field_OpenList) {
                $form_element_representation = Tracker_REST_FormElement_FieldOpenListRepresentation::build(
                    $form_element,
                    $this->form_element_factory->getType($form_element),
                    $this->getPermissionsForFormElement($form_element, $artifact, $user),
                    $this->permissions_for_groups_builder->getPermissionsForGroups($form_element, $artifact, $user)
                );
            } elseif ($artifact !== null && $form_element instanceof Tracker_FormElement_Container_Fieldset) {
                $form_element_representation = ContainerFieldsetInArtifactContextRepresentation::buildContainerFieldset(
                    $form_element,
                    $this->form_element_factory->getType($form_element),
                    $this->getPermissionsForFormElement($form_element, $artifact, $user),
                    $this->permissions_for_groups_builder->getPermissionsForGroups($form_element, $artifact, $user),
                    $this->hidden_fieldset_checker->mustFieldsetBeHidden($form_element, $artifact)
                );
            } elseif ($form_element instanceof \Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField) {
                $form_element_representation = LinksFieldRepresentation::buildRepresentationWithAllowedLinkTypes(
                    $form_element,
                    $this->form_element_factory->getType($form_element),
                    $this->getPermissionsForFormElement($form_element, $artifact, $user),
                    $this->types_allowed_retriever->getAllUsableTypesInProject($tracker->getProject()),
                    $this->permissions_for_groups_builder->getPermissionsForGroups($form_element, $artifact, $user),
                );
            } else {
                $form_element_representation = Tracker_REST_FormElementRepresentation::build(
                    $form_element,
                    $this->form_element_factory->getType($form_element),
                    $this->getPermissionsForFormElement($form_element, $artifact, $user),
                    $this->permissions_for_groups_builder->getPermissionsForGroups($form_element, $artifact, $user)
                );
            }

            $representation_collection[] = $form_element_representation;
        }

        return $representation_collection;
    }

    private function getPermissionsForFormElement(
        Tracker_FormElement $form_element,
        ?Artifact $artifact,
        PFUser $user,
    ): array {
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
