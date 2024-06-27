<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\UGroupList;

use LogicException;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectResultKey;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\Project\UGroupRetriever;
use Tuleap\Tracker\Artifact\RetrieveArtifact;

final readonly class UGroupListResultBuilder
{
    public function __construct(
        private RetrieveArtifact $artifact_retriever,
        private UGroupRetriever $user_group_retriever,
    ) {
    }

    public function getResult(DuckTypedFieldSelect $field, array $select_results): SelectedValuesCollection
    {
        $values = [];
        $alias  = SelectResultKey::fromDuckTypedField($field);

        foreach ($select_results as $result) {
            $id = $result['id'];
            if (! isset($values[$id])) {
                $values[$id] = [];
            }

            if ($result["user_group_list_value_$alias"] !== null) {
                $name = $result["user_group_list_value_$alias"];
            } elseif ($result["user_group_list_open_$alias"] !== null) {
                $name = $result["user_group_list_open_$alias"];
            } else {
                continue;
            }

            $artifact = $this->artifact_retriever->getArtifactById($id);
            if ($artifact === null) {
                throw new LogicException("Artifact #$id not found");
            }
            $user_group = $this->user_group_retriever->getUGroupByName($artifact->getTracker()->getProject(), $name);
            if ($user_group === null) {
                throw new LogicException("User Group $name not found");
            }

            $values[$id][] = UGroupListValueRepresentation::fromProjectUGroup($user_group);
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($field->name, CrossTrackerSelectedType::TYPE_USER_GROUP_LIST),
            array_map(static fn(array $selected_values) => new SelectedValue($field->name, new UGroupListRepresentation($selected_values)), $values),
        );
    }
}
