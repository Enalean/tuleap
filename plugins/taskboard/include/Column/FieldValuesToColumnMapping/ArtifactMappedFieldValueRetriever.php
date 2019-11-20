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

class ArtifactMappedFieldValueRetriever
{
    /**
     * @var MappedFieldRetriever
     */
    private $mapped_field_retriever;

    public function __construct(MappedFieldRetriever $mapped_field_retriever)
    {
        $this->mapped_field_retriever = $mapped_field_retriever;
    }

    public static function build(): self
    {
        return new self(MappedFieldRetriever::build());
    }

    public function getValueAtLastChangeset(
        \Planning_Milestone $milestone,
        \Tracker_Artifact $artifact,
        \PFUser $user
    ): ?Tracker_FormElement_Field_List_BindValue {
        $taskboard_tracker = new TaskboardTracker($milestone->getArtifact()->getTracker(), $artifact->getTracker());
        $mapped_field = $this->mapped_field_retriever->getField($taskboard_tracker);
        if (! $mapped_field) {
            return null;
        }

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
    }
}
