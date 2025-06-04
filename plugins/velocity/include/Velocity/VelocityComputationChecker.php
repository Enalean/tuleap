<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Velocity;

use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticVelocity;

class VelocityComputationChecker
{
    public function shouldComputeCapacity(
        TrackerSemanticStatus $semantic_status,
        SemanticDone $semantic_done,
        SemanticVelocity $semantic_velocity,
        BeforeEvent $before_event,
    ) {
        if (! $semantic_status->getFieldId() || $semantic_status->getField()?->isMultiple()) {
            return false;
        }

        $field_id = $semantic_velocity->getFieldId();
        if (! $semantic_done->isSemanticDefined() || ! $field_id) {
            return false;
        }

        $last_changeset_semantic_status_values = $this->getLastChangesetValues($semantic_status, $before_event);
        $new_semantic_status_values            = $this->getNewChangesetValues($semantic_status, $before_event);

        if ($last_changeset_semantic_status_values === $new_semantic_status_values) {
            return false;
        }

        $open_values = $semantic_status->getOpenValues();
        $done_values = $semantic_done->getDoneValuesIds();

        return ! empty(array_intersect($last_changeset_semantic_status_values, $open_values))
            && ! empty(array_intersect($new_semantic_status_values, $done_values));
    }

    private function getLastChangesetValues(TrackerSemanticStatus $semantic_status, BeforeEvent $before_event)
    {
        $last_changeset        = $before_event->getArtifact()->getLastChangeset();
        $values                = [];
        $semantic_status_field = $semantic_status->getField();
        if ($last_changeset && $semantic_status_field) {
            $last_semantic_status_value = $last_changeset->getValue($semantic_status_field);
            if ($last_semantic_status_value) {
                $values = $last_semantic_status_value->getValue();
            }
        }

        return (array) $values;
    }

    private function getNewChangesetValues(TrackerSemanticStatus $semantic_status, BeforeEvent $before_event)
    {
        $new_values           = $before_event->getFieldsData();
        $values               = [];
        $smantic_status_field = $semantic_status->getField();
        if ($smantic_status_field && isset($new_values[$smantic_status_field->getId()])) {
            $values = (array) $new_values[$smantic_status_field->getId()];
        }

        return $values;
    }
}
