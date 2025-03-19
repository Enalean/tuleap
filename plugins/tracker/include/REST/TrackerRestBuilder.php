<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\StructureElementRepresentation;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\v1\BuildCompleteTrackerRESTRepresentation;
use Tuleap\Tracker\REST\WorkflowRestBuilder;

class Tracker_REST_TrackerRestBuilder implements BuildCompleteTrackerRESTRepresentation // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct(
        private Tracker_FormElementFactory $formelement_factory,
        private FormElementRepresentationsBuilder $form_element_representations_builder,
        private PermissionsRepresentationBuilder $permissions_representation_builder,
        private WorkflowRestBuilder $workflow_rest_builder,
    ) {
    }

    public function getTrackerRepresentationInTrackerContext(PFUser $user, Tracker $tracker): CompleteTrackerRepresentation
    {
        return $this->buildTrackerRepresentation(
            $user,
            $tracker,
            $this->form_element_representations_builder->buildRepresentationsInTrackerContext(
                $tracker,
                $user
            )
        );
    }

    public function getTrackerRepresentationInArtifactContext(PFUser $user, Artifact $artifact): CompleteTrackerRepresentation
    {
        $tracker = $artifact->getTracker();

        return $this->buildTrackerRepresentation(
            $user,
            $tracker,
            $this->form_element_representations_builder->buildRepresentationsInArtifactContext(
                $artifact,
                $user
            )
        );
    }

    private function buildTrackerRepresentation(PFUser $user, Tracker $tracker, array $rest_fields): CompleteTrackerRepresentation
    {
        $semantic_manager = $this->getSemanticManager($tracker);
        return CompleteTrackerRepresentation::build(
            $tracker,
            $rest_fields,
            $this->getStructureRepresentation($tracker),
            $semantic_manager->exportToREST($user),
            $tracker->getParentUserCanView($user),
            $this->workflow_rest_builder->getWorkflowRepresentation($tracker->getWorkflow(), $user),
            $this->permissions_representation_builder->getPermissionsRepresentation($tracker, $user)
        );
    }

    /**
     * This is for tests
     * I know it's crappy but there is no clean alternative as semantic manager
     * requires a tracker as a parameter (I cannot pass it as constructor argument
     * because the tracker is an argument of the method, not the class).
     *
     * @return Tracker_SemanticManager
     */
    protected function getSemanticManager(Tracker $tracker)
    {
        return new Tracker_SemanticManager($tracker);
    }

    private function getStructureRepresentation(Tracker $tracker)
    {
        $structure_element_representations = [];
        $form_elements                     = $this->formelement_factory->getUsedFormElementForTracker($tracker);

        if ($form_elements) {
            foreach ($form_elements as $form_element) {
                $structure_element_representation = new StructureElementRepresentation();
                $structure_element_representation->build($form_element);

                $structure_element_representations[] = $structure_element_representation;
            }
        }

        return $structure_element_representations;
    }
}
