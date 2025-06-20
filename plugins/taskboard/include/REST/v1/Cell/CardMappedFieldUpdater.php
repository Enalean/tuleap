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

declare(strict_types=1);

namespace Tuleap\Taskboard\REST\v1\Cell;

use Cardwall_Column;
use Luracast\Restler\RestException;
use PFUser;
use Tracker_Exception;
use Tracker_FormElement_Field_Selectbox;
use Tracker_FormElement_InvalidFieldException;
use Tracker_FormElement_InvalidFieldValueException;
use Tracker_NoChangeException;
use Tuleap\Cardwall\OnTop\Config\ColumnFactory;
use Tuleap\REST\I18NRestException;
use Tuleap\Taskboard\Column\CardColumnFinder;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedFieldRetriever;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\MappedValuesRetriever;
use Tuleap\Taskboard\Column\InvalidColumnException;
use Tuleap\Taskboard\Column\MilestoneTrackerRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\REST\Artifact\ArtifactUpdater;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\FirstPossibleValueInListRetriever;
use Tuleap\Tracker\Workflow\NoPossibleValueException;

class CardMappedFieldUpdater
{
    public function __construct(
        private ColumnFactory $column_factory,
        private MilestoneTrackerRetriever $milestone_tracker_retriever,
        private AddValidator $add_validator,
        private MappedFieldRetriever $mapped_field_retriever,
        private MappedValuesRetriever $mapped_values_retriever,
        private FirstPossibleValueInListRetriever $first_possible_value_retriever,
        private CardColumnFinder $column_finder,
        private ArtifactUpdater $artifact_updater,
    ) {
    }

    /**
     * @throws I18NRestException
     * @throws RestException
     */
    public function updateCardMappedField(
        Artifact $swimlane_artifact,
        int $column_id,
        Artifact $artifact_to_add,
        PFUser $current_user,
    ): void {
        $column            = $this->getColumn($column_id);
        $milestone_tracker = $this->getMilestoneTracker($column);
        $this->add_validator->validateArtifacts($swimlane_artifact, $artifact_to_add, $current_user);

        $is_already_in_the_target_column = $this->column_finder->findColumnOfCard(
            $milestone_tracker,
            $artifact_to_add,
            $current_user
        )->mapOr(static fn(\Cardwall_Column $column) => (int) $column->getId() === $column_id, false);
        if ($is_already_in_the_target_column) {
            return;
        }

        $values = $this->buildUpdateValues(
            $artifact_to_add,
            new TaskboardTracker($milestone_tracker, $artifact_to_add->getTracker()),
            $column,
            $current_user
        );
        try {
            $this->artifact_updater->update($current_user, $artifact_to_add, $values);
        } catch (Tracker_FormElement_InvalidFieldException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_FormElement_InvalidFieldValueException $exception) {
            throw new RestException(400, $exception->getMessage());
        } catch (Tracker_NoChangeException $exception) {
            // Do nothing
        } catch (Tracker_Exception $exception) {
            if ($GLOBALS['Response']->feedbackHasErrors()) {
                throw new RestException(500, $GLOBALS['Response']->getRawFeedback());
            }
            throw new RestException(500, $exception->getMessage());
        }
    }

    /**
     * @throws RestException
     */
    private function getColumn(int $id): Cardwall_Column
    {
        $column = $this->column_factory->getColumnById($id);
        if ($column === null) {
            throw new RestException(404);
        }
        return $column;
    }

    /**
     * @return ArtifactValuesRepresentation[]
     * @throws I18NRestException
     */
    private function buildUpdateValues(
        Artifact $artifact_to_add,
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
        PFUser $current_user,
    ): array {
        $representation                 = new ArtifactValuesRepresentation();
        $mapped_field                   = $this->getMappedField($taskboard_tracker, $column, $current_user);
        $representation->field_id       = (int) $mapped_field->getId();
        $first_mapped_value             = $this->getFirstMappedValue(
            $mapped_field,
            $artifact_to_add,
            $taskboard_tracker,
            $column,
            $current_user
        );
        $representation->bind_value_ids = [$first_mapped_value];

        return [$representation];
    }

    /**
     * @throws I18NRestException
     */
    private function getMappedField(
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
        PFUser $current_user,
    ): Tracker_FormElement_Field_Selectbox {
        return $this->mapped_field_retriever->getField($taskboard_tracker)
            ->match(function ($mapped_field) use ($current_user): \Tracker_FormElement_Field_Selectbox {
                if (! $mapped_field->userCanUpdate($current_user)) {
                    throw new I18NRestException(
                        403,
                        sprintf(
                            dgettext('tuleap-taskboard', "You don't have permission to update the %s field."),
                            $mapped_field->getLabel()
                        )
                    );
                }
                return $mapped_field;
            }, fn() => throw new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-taskboard',
                        'Tracker %s has no list field mapped to column %s, please check its configuration.'
                    ),
                    $taskboard_tracker->getTracker()->getName(),
                    $column->getLabel()
                )
            ));
    }

    /**
     * @throws I18NRestException
     */
    private function getFirstMappedValue(
        Tracker_FormElement_Field_Selectbox $mapped_field,
        Artifact $artifact_to_add,
        TaskboardTracker $taskboard_tracker,
        Cardwall_Column $column,
        PFUser $user,
    ): int {
        return $this->mapped_values_retriever->getValuesMappedToColumn($taskboard_tracker, $column)
            ->match(function ($mapped_values) use ($mapped_field, $artifact_to_add, $taskboard_tracker, $column, $user) {
                if ($mapped_values->isEmpty()) {
                    throw new I18NRestException(
                        400,
                        sprintf(
                            dgettext(
                                'tuleap-taskboard',
                                'Tracker %s has no value mapped to column %s, please check its configuration.'
                            ),
                            $taskboard_tracker->getTracker()->getName(),
                            $column->getLabel()
                        )
                    );
                }
                try {
                    return $this->first_possible_value_retriever->getFirstPossibleValue(
                        $artifact_to_add,
                        $mapped_field,
                        $mapped_values,
                        $user
                    );
                } catch (NoPossibleValueException $exception) {
                    throw new I18NRestException(
                        400,
                        $exception->getMessage()
                    );
                }
            }, static fn () => throw new I18NRestException(
                400,
                sprintf(
                    dgettext(
                        'tuleap-taskboard',
                        'Tracker %s has no value mapped to column %s, please check its configuration.'
                    ),
                    $taskboard_tracker->getTracker()->getName(),
                    $column->getLabel()
                )
            ));
    }

    /**
     * @throws RestException
     */
    private function getMilestoneTracker(Cardwall_Column $column): Tracker
    {
        try {
            $milestone_tracker = $this->milestone_tracker_retriever->getMilestoneTrackerOfColumn($column);
        } catch (InvalidColumnException $e) {
            throw new RestException(404);
        }
        return $milestone_tracker;
    }
}
