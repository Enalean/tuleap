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

namespace Tuleap\Tracker\Workflow;

use Tracker_Artifact;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

class WorkflowUpdateChecker
{
    /** @var FrozenFieldDetector */
    private $frozen_field_detector;

    public function __construct(FrozenFieldDetector $frozen_field_detector)
    {
        $this->frozen_field_detector = $frozen_field_detector;
    }

    public function canFieldBeUpdated(
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field $field,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value,
        $submitted_value,
        bool $is_submission,
        \PFUser $user
    ): bool {
        if ($is_submission || $user instanceof \Tracker_Workflow_WorkflowUser) {
            return true;
        }

        if (! $this->fieldHasChanges($artifact, $field, $last_changeset_value, $submitted_value)) {
            return true;
        }

        return ! $this->frozen_field_detector->isFieldFrozen($artifact, $field);
    }

    private function fieldHasChanges(
        Tracker_Artifact $artifact,
        Tracker_FormElement_Field $field,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value,
        $submitted_value
    ): bool {
        if ($submitted_value === null) {
            return false;
        }

        if ($last_changeset_value === null && $submitted_value !== null) {
            return true;
        }

        if ($field->hasChanges($artifact, $last_changeset_value, $submitted_value)) {
            return true;
        }

        return false;
    }
}
