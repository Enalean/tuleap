<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1\Scrum\BacklogItem;

use AgileDashboard_Milestone_Backlog_BacklogItem;
use AgileDashBoard_Semantic_InitialEffort;
use PFUser;
use Tracker_FormElement_Field_Computed;
use Tracker_FormElement_Field_Selectbox;

class InitialEffortSemanticUpdater
{
    public function updateBacklogItemInitialEffortSemantic(
        PFUser $current_user,
        AgileDashboard_Milestone_Backlog_BacklogItem $backlog_item,
        AgileDashBoard_Semantic_InitialEffort $semantic_initial_effort
    ): AgileDashboard_Milestone_Backlog_BacklogItem {
        $artifact = $backlog_item->getArtifact();

        $initial_effort_field = $semantic_initial_effort->getField();

        if ($initial_effort_field && $initial_effort_field->userCanRead($current_user)) {
            $last_changeset = $artifact->getLastChangeset();
            if ($last_changeset === null) {
                return $backlog_item;
            }

            $rest_value = $initial_effort_field->getFullRESTValue($current_user, $last_changeset);
            if ($rest_value) {
                if (
                    $initial_effort_field instanceof Tracker_FormElement_Field_Selectbox ||
                    $initial_effort_field instanceof Tracker_FormElement_Field_Computed
                ) {
                    $value = $initial_effort_field->getComputedValue($current_user, $artifact);
                } else {
                    $value = $rest_value->value;
                }

                $backlog_item->setInitialEffort($value);
            }
        }

        return $backlog_item;
    }
}
