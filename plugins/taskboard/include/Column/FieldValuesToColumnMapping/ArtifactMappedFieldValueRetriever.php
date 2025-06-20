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

use Tuleap\Option\Option;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\Artifact\Artifact;

final readonly class ArtifactMappedFieldValueRetriever
{
    public function __construct(private MappedFieldRetriever $mapped_field_retriever)
    {
    }

    /** @return Option<\Tracker_FormElement_Field_List_BindValue> */
    public function getFirstValueAtLastChangeset(
        \Tuleap\Tracker\Tracker $milestone_tracker,
        Artifact $artifact,
        \PFUser $user,
    ): Option {
        $taskboard_tracker = new TaskboardTracker($milestone_tracker, $artifact->getTracker());
        return $this->mapped_field_retriever->getField($taskboard_tracker)
            ->andThen(function (\Tracker_FormElement_Field_Selectbox $mapped_field) use ($artifact, $user) {
                if (! $mapped_field->userCanRead($user)) {
                    return Option::nothing(\Tracker_FormElement_Field_List_BindValue::class);
                }

                $last_changeset = $artifact->getLastChangeset();
                if (! $last_changeset) {
                    return Option::nothing(\Tracker_FormElement_Field_List_BindValue::class);
                }

                $value = $last_changeset->getValue($mapped_field);
                if (! ($value instanceof \Tracker_Artifact_ChangesetValue_List)) {
                    return Option::nothing(\Tracker_FormElement_Field_List_BindValue::class);
                }

                $values = array_values($value->getListValues());
                if (count($values) === 0) {
                    return Option::nothing(\Tracker_FormElement_Field_List_BindValue::class);
                }
                return Option::fromValue($values[0]);
            });
    }
}
