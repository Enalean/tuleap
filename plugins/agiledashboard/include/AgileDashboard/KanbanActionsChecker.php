<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\AgileDashboard\KanbanUserCantAddArtifactException;
use Tuleap\Kanban\Kanban;

class AgileDashboard_KanbanActionsChecker
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var AgileDashboard_PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var Tracker_Factory
     */
    private $tracker_factory;

    public function __construct(
        TrackerFactory $tracker_factory,
        AgileDashboard_PermissionsManager $permissions_manager,
        Tracker_FormElementFactory $form_element_factory,
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->permissions_manager  = $permissions_manager;
        $this->tracker_factory      = $tracker_factory;
    }

    /**
     * @throws KanbanUserCantAddArtifactException
     * @throws Kanban_SemanticStatusNotDefinedException
     * @throws Kanban_TrackerNotDefinedException
     */
    public function checkUserCanAddArtifact(PFUser $user, Kanban $kanban): void
    {
        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (
            ! $tracker->userCanSubmitArtifact($user) ||
            ! $semantic_status->getField()->userCanSubmit($user)
        ) {
            throw new KanbanUserCantAddArtifactException();
        }
    }

    /**
     * @throws KanbanUserCantAddArtifactException
     * @throws Kanban_SemanticTitleNotDefinedException
     * @throws Kanban_TrackerNotDefinedException
     * @throws Kanban_UserCantAddInPlaceException
     */
    public function checkUserCanAddInPlace(PFUser $user, Kanban $kanban): void
    {
        $tracker        = $this->getTrackerForKanban($kanban);
        $semantic_title = $this->getSemanticTitle($tracker);

        $this->checkUserCanAddArtifact($user, $kanban);

        if (
            ! $this->trackerHasOnlyTitleRequired($tracker, $semantic_title)
        ) {
            throw new Kanban_UserCantAddInPlaceException();
        }
    }

    public function checkUserCanAddColumns(PFUser $user, Kanban $kanban)
    {
        $this->checkUserCanAdministrate($user, $kanban);

        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            throw new Kanban_SemanticStatusNotBoundToStaticValuesException();
        }

        if ($semantic_status->isBasedOnASharedField()) {
            throw new Kanban_SemanticStatusBasedOnASharedFieldException();
        }
    }

    public function checkUserCanReorderColumns(PFUser $user, Kanban $kanban)
    {
        $this->checkUserCanAdministrate($user, $kanban);

        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            throw new Kanban_SemanticStatusNotBoundToStaticValuesException();
        }

        if ($semantic_status->isBasedOnASharedField()) {
            throw new Kanban_SemanticStatusBasedOnASharedFieldException();
        }
    }

    public function checkUserCanAdministrate(PFUser $user, Kanban $kanban)
    {
        $tracker = $this->getTrackerForKanban($kanban);

        if (! $this->permissions_manager->userCanAdministrate($user, $tracker->getProject()->getId())) {
            throw new AgileDashboard_UserNotAdminException($user);
        }
    }

    public function checkUserCanDeleteColumn(PFUser $user, Kanban $kanban, AgileDashboard_KanbanColumn $column)
    {
        $this->checkUserCanAdministrate($user, $kanban);

        if (! $column->isRemovable()) {
            throw new AgileDashboard_KanbanColumnNotRemovableException();
        }

        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            throw new Kanban_SemanticStatusNotBoundToStaticValuesException();
        }

        if ($semantic_status->isBasedOnASharedField()) {
            throw new Kanban_SemanticStatusBasedOnASharedFieldException();
        }
    }

    public function checkUserCanEditColumnLabel(PFUser $user, Kanban $kanban)
    {
        $this->checkUserCanAdministrate($user, $kanban);

        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            throw new Kanban_SemanticStatusNotBoundToStaticValuesException();
        }

        if ($semantic_status->isBasedOnASharedField()) {
            throw new Kanban_SemanticStatusBasedOnASharedFieldException();
        }
    }

    public function getTrackerForKanban(Kanban $kanban)
    {
        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());

        if (! $tracker) {
            throw new Kanban_TrackerNotDefinedException();
        }

        return $tracker;
    }

    public function getSemanticStatus(Tracker $tracker)
    {
        $semantic = Tracker_Semantic_Status::load($tracker);

        if (! $semantic->getFieldId()) {
            throw new Kanban_SemanticStatusNotDefinedException();
        }

        return $semantic;
    }

    private function getSemanticTitle(Tracker $tracker)
    {
        $semantic = Tracker_Semantic_Title::load($tracker);

        if (! $semantic->getFieldId()) {
            throw new Kanban_SemanticTitleNotDefinedException();
        }

        return $semantic;
    }

    private function trackerHasOnlyTitleRequired(Tracker $tracker, Tracker_Semantic_Title $semantic_title)
    {
        $used_fields = $this->form_element_factory->getUsedFields($tracker);

        foreach ($used_fields as $used_field) {
            if ($used_field->isRequired() && $used_field->getId() != $semantic_title->getFieldId()) {
                return false;
            }
        }

        return true;
    }
}
