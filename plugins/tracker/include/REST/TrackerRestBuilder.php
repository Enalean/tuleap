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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;
use Tuleap\Tracker\REST\CompleteTrackerRepresentation;
use Tuleap\Tracker\REST\FormElementRepresentationsBuilder;
use Tuleap\Tracker\REST\StructureElementRepresentation;
use Tuleap\Tracker\REST\Tracker\PermissionsRepresentationBuilder;
use Tuleap\Tracker\REST\v1\BuildCompleteTrackerRESTRepresentation;
use Tuleap\Tracker\REST\WorkflowRestBuilder;

class Tracker_REST_TrackerRestBuilder implements BuildCompleteTrackerRESTRepresentation // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    /**
     * @psalm-param \Closure(Tracker): \Tracker_SemanticManager $semantic_manager_instantiator
     */
    public function __construct(
        private Tracker_FormElementFactory $formelement_factory,
        private FormElementRepresentationsBuilder $form_element_representations_builder,
        private PermissionsRepresentationBuilder $permissions_representation_builder,
        private WorkflowRestBuilder $workflow_rest_builder,
        private \Closure $semantic_manager_instantiator,
        private ParentInHierarchyRetriever $parent_tracker_retriever,
        private RetrieveUserPermissionOnTrackers $tracker_permissions_retriever,
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
        $parent_tracker   = $this->getParentTrackerUserCanRead($tracker, $user)->unwrapOr(null);
        $semantic_manager = ($this->semantic_manager_instantiator)($tracker);
        return CompleteTrackerRepresentation::build(
            $tracker,
            $rest_fields,
            $this->getStructureRepresentation($tracker),
            $semantic_manager->exportToREST($user),
            $parent_tracker,
            $this->workflow_rest_builder->getWorkflowRepresentation($tracker->getWorkflow(), $user),
            $this->permissions_representation_builder->getPermissionsRepresentation($tracker, $user)
        );
    }

    private function getStructureRepresentation(Tracker $tracker): array
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

    /**
     * @return Option<Tracker>
     */
    private function getParentTrackerUserCanRead(Tracker $child_tracker, PFUser $user): Option
    {
        return $this->parent_tracker_retriever->getParentTracker($child_tracker)
            ->andThen(function (Tracker $parent_tracker) use ($user) {
                $permissions               = $this->tracker_permissions_retriever->retrieveUserPermissionOnTrackers(
                    $user,
                    [$parent_tracker],
                    TrackerPermissionType::PERMISSION_VIEW
                );
                $parent_tracker_is_allowed = array_search($parent_tracker, $permissions->allowed, true);
                if ($parent_tracker_is_allowed !== false) {
                    return Option::fromValue($parent_tracker);
                }
                return Option::nothing(Tracker::class);
            });
    }
}
