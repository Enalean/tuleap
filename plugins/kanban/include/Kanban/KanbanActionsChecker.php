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

declare(strict_types=1);

namespace Tuleap\Kanban;

use PFUser;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use Tuleap\Tracker\RetrieveTracker;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatus;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;
use Tuleap\Tracker\Tracker;

class KanbanActionsChecker
{
    public function __construct(
        private readonly RetrieveTracker $tracker_factory,
        private readonly KanbanPermissionsManager $permissions_manager,
        private readonly RetrieveUsedFields $form_element_factory,
        private readonly VerifySubmissionPermissions $verify_submission_permissions,
        private readonly RetrieveSemanticTitleField $title_field_retriever,
        private readonly RetrieveSemanticStatus $semantic_status_retriever,
    ) {
    }

    /**
     * @throws KanbanUserCantAddArtifactException
     * @throws KanbanSemanticStatusNotDefinedException
     * @throws KanbanTrackerNotDefinedException
     */
    public function checkUserCanAddArtifact(PFUser $user, Kanban $kanban): void
    {
        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker)->getField();

        if (
            ! $this->verify_submission_permissions->canUserSubmitArtifact($user, $tracker) ||
            ! $semantic_status ||
            ! $semantic_status->userCanSubmit($user)
        ) {
            throw new KanbanUserCantAddArtifactException();
        }
    }

    /**
     * @throws KanbanUserCantAddArtifactException
     * @throws KanbanSemanticTitleNotDefinedException
     * @throws KanbanTrackerNotDefinedException
     * @throws KanbanUserCantAddInPlaceException
     */
    public function checkUserCanAddInPlace(PFUser $user, Kanban $kanban): void
    {
        $tracker        = $this->getTrackerForKanban($kanban);
        $semantic_title = $this->getSemanticTitle($tracker);

        $this->checkUserCanAddArtifact($user, $kanban);

        if (
            ! $this->trackerHasOnlyTitleRequired($tracker, $semantic_title)
        ) {
            throw new KanbanUserCantAddInPlaceException();
        }
    }

    public function checkUserCanAddColumns(PFUser $user, Kanban $kanban): void
    {
        $this->checkUserCanAdministrate($user, $kanban);

        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            throw new KanbanSemanticStatusNotBoundToStaticValuesException();
        }

        if ($semantic_status->isBasedOnASharedField()) {
            throw new KanbanSemanticStatusBasedOnASharedFieldException();
        }
    }

    public function checkUserCanReorderColumns(PFUser $user, Kanban $kanban): void
    {
        $this->checkUserCanAdministrate($user, $kanban);

        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            throw new KanbanSemanticStatusNotBoundToStaticValuesException();
        }

        if ($semantic_status->isBasedOnASharedField()) {
            throw new KanbanSemanticStatusBasedOnASharedFieldException();
        }
    }

    public function checkUserCanAdministrate(PFUser $user, Kanban $kanban): void
    {
        $tracker = $this->getTrackerForKanban($kanban);

        if (! $this->permissions_manager->userCanAdministrate($user, $tracker->getProject())) {
            throw new KanbanUserNotAdminException($user);
        }
    }

    public function checkUserCanDeleteColumn(PFUser $user, Kanban $kanban, KanbanColumn $column): void
    {
        $this->checkUserCanAdministrate($user, $kanban);

        if (! $column->isRemovable()) {
            throw new KanbanColumnNotRemovableException();
        }

        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            throw new KanbanSemanticStatusNotBoundToStaticValuesException();
        }

        if ($semantic_status->isBasedOnASharedField()) {
            throw new KanbanSemanticStatusBasedOnASharedFieldException();
        }
    }

    public function checkUserCanEditColumnLabel(PFUser $user, Kanban $kanban): void
    {
        $this->checkUserCanAdministrate($user, $kanban);

        $tracker         = $this->getTrackerForKanban($kanban);
        $semantic_status = $this->getSemanticStatus($tracker);

        if (! $semantic_status->isFieldBoundToStaticValues()) {
            throw new KanbanSemanticStatusNotBoundToStaticValuesException();
        }

        if ($semantic_status->isBasedOnASharedField()) {
            throw new KanbanSemanticStatusBasedOnASharedFieldException();
        }
    }

    public function getTrackerForKanban(Kanban $kanban): Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($kanban->getTrackerId());

        if (! $tracker) {
            throw new KanbanTrackerNotDefinedException();
        }

        return $tracker;
    }

    public function getSemanticStatus(Tracker $tracker): TrackerSemanticStatus
    {
        $semantic = $this->semantic_status_retriever->fromTracker($tracker);

        if ($semantic->getField() === null) {
            throw new KanbanSemanticStatusNotDefinedException();
        }

        return $semantic;
    }

    private function getSemanticTitle(Tracker $tracker): TrackerSemanticTitle
    {
        $title_field = $this->title_field_retriever->fromTracker($tracker);

        if ($title_field === null) {
            throw new KanbanSemanticTitleNotDefinedException();
        }

        return new TrackerSemanticTitle($tracker, $title_field);
    }

    private function trackerHasOnlyTitleRequired(Tracker $tracker, TrackerSemanticTitle $semantic_title): bool
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
