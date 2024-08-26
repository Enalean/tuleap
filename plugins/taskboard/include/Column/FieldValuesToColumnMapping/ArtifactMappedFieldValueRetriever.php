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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Artifact\Artifact;

class ArtifactMappedFieldValueRetriever
{
    public function __construct(private MappedFieldRetriever $mapped_field_retriever)
    {
    }

    public function getValueAtLastChangeset(
        \Planning_Milestone $milestone,
        Artifact $artifact,
        \PFUser $user,
    ): ?Tracker_FormElement_Field_List_BindValue {
        $taskboard_tracker = new TaskboardTracker($milestone->getArtifact()->getTracker(), $artifact->getTracker());
        return $this->mapped_field_retriever->getField($taskboard_tracker)
            ->mapOr(function (\Tracker_FormElement_Field_Selectbox $mapped_field) use ($artifact, $user) {
                if (! $mapped_field->userCanRead($user)) {
                    return null;
                }

                $last_changeset = $artifact->getLastChangeset();
                if (! $last_changeset) {
                    return null;
                }

                $value = $last_changeset->getValue($mapped_field);
                if (! $value instanceof \Tracker_Artifact_ChangesetValue_List) {
                    return null;
                }

                $values = $value->getListValues();
                if (count($values) === 0) {
                    return null;
                }

                reset($values);

                return current($values);
            }, null);
    }
}
