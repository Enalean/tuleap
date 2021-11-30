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

use Tracker_Semantic_Status;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Workflow\BeforeEvent;
use Tuleap\Velocity\Semantic\SemanticVelocity;

class VelocityComputationChecker
{
    public function shouldComputeCapacity(
        Tracker_Semantic_Status $semantic_status,
        SemanticDone $semantic_done,
        SemanticVelocity $semantic_velocity,
        BeforeEvent $before_event,
    ) {
        if (! $semantic_status->getFieldId() || $semantic_status->getField()->isMultiple()) {
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

    private function getLastChangesetValues(Tracker_Semantic_Status $semantic_status, BeforeEvent $before_event)
    {
        $last_changeset = $before_event->getArtifact()->getLastChangeset();
        $values         = [];
        if ($last_changeset) {
            $last_semantic_status_value = $last_changeset->getValue($semantic_status->getField());
            if ($last_semantic_status_value) {
                $values = $last_semantic_status_value->getValue();
            }
        }

        return (array) $values;
    }

    private function getNewChangesetValues(Tracker_Semantic_Status $semantic_status, BeforeEvent $before_event)
    {
        $new_values = $before_event->getFieldsData();
        $values     = [];
        if (isset($new_values[$semantic_status->getField()->getId()])) {
            $values = (array) $new_values[$semantic_status->getField()->getId()];
        }

        return $values;
    }
}
