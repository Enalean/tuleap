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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\UGroupList;

use LogicException;
use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UGroupListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\UGroupListValueRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Query\Advanced\SelectResultKey;
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

            $names = [];
            if ($result["user_group_list_value_$alias"] !== null) {
                if (is_array($result["user_group_list_value_$alias"])) {
                    $names = array_merge($names, $result["user_group_list_value_$alias"]);
                } else {
                    $names[] = $result["user_group_list_value_$alias"];
                }
            }
            if ($result["user_group_list_open_$alias"] !== null) {
                if (is_array($result["user_group_list_open_$alias"])) {
                    $names = array_merge($names, $result["user_group_list_open_$alias"]);
                } else {
                    $names[] = $result["user_group_list_open_$alias"];
                }
            }

            if ($names === []) {
                continue;
            }

            $artifact = $this->artifact_retriever->getArtifactById($id);
            if ($artifact === null) {
                throw new LogicException("Artifact #$id not found");
            }
            $values[$id] = array_map(
                function (string $name) use ($artifact) {
                    $user_group = $this->user_group_retriever->getUGroupByName($artifact->getTracker()->getProject(), $name);
                    if ($user_group === null) {
                        throw new LogicException("User Group $name not found");
                    }
                    return UGroupListValueRepresentation::fromProjectUGroup($user_group);
                },
                array_filter($names),
            );
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($field->name, CrossTrackerSelectedType::TYPE_USER_GROUP_LIST),
            array_map(static fn(array $selected_values) => new SelectedValue($field->name, new UGroupListRepresentation($selected_values)), $values),
        );
    }
}
