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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\StaticList;

use Tuleap\CrossTracker\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Query\Advanced\SelectResultKey;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;

final class StaticListResultBuilder
{
    public function getResult(DuckTypedFieldSelect $field, array $select_results): SelectedValuesCollection
    {
        $values = [];
        $alias  = SelectResultKey::fromDuckTypedField($field);

        foreach ($select_results as $result) {
            $id = $result['id'];

            $labels = [];
            if ($result["static_list_value_$alias"] !== null) {
                if (is_array($result["static_list_value_$alias"])) {
                    $labels = array_merge($labels, $result["static_list_value_$alias"]);
                } else {
                    $labels[] = $result["static_list_value_$alias"];
                }
            }
            if ($result["static_list_open_$alias"] !== null) {
                if (is_array($result["static_list_open_$alias"])) {
                    $labels = array_merge($labels, $result["static_list_open_$alias"]);
                } else {
                    $labels[] = $result["static_list_open_$alias"];
                }
            }
            $color = $result["static_list_color_$alias"];

            $i           = 0;
            $values[$id] = array_map(
                static function (string $label) use (&$i, $color) {
                    return new StaticListValueRepresentation(
                        $label,
                        is_array($color) ? ($color[$i++] ?? null) : $color,
                    );
                },
                array_filter($labels),
            );
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($field->name, CrossTrackerSelectedType::TYPE_STATIC_LIST),
            array_map(static fn(array $selected_values) => new SelectedValue($field->name, new StaticListRepresentation($selected_values)), $values),
        );
    }
}
