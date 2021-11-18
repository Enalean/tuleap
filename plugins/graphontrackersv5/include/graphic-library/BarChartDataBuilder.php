<?php
/**
 * Copyright (c) Enalean 2021- Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

namespace Tuleap\GraphOnTrackersV5\GraphicLibrary;

use GraphOnTrackersV5_Engine_Bar;

final class BarChartDataBuilder
{
    public function buildGroupedBarChartData(GraphOnTrackersV5_Engine_Bar $engine): array
    {
        $values = [];
        foreach ($this->getGroupedValuesBySource($engine) as $source_key => $source_values) {
            $grouped_source_values = $this->buildValuesGroupByLabel($engine, $source_values);
            $values                = $this->buildGroupByFieldValues($engine, $source_key, $grouped_source_values, $values);
        }

        return $this->reorderKeyRespectingXaxisOrder($engine, $values);
    }

    private function getGroupedValuesBySource(GraphOnTrackersV5_Engine_Bar $engine): array
    {
        $grouped_values_by_source = [];
        foreach ($engine->getDataAsArray() as $group_by => $source_data_values) {
            foreach ($source_data_values as $source_data_key => $value) {
                $grouped_values_by_source[$source_data_key][$group_by] = $value;
            }
        }

        return $grouped_values_by_source;
    }

    /**
     *
     * $engine->legend = [
     *      '' => 'None',
     *      1 => 'Abc',
     *      2 => 'Def',
     * ];
     * $engine->data   = [
     *      '' => [100 => 10, 101 => 11],
     *      1  => [102 => 13],
     *      2  => [102 => 14, 103 => 15]
     * ];
     *
     * will return for 103
     * [
     *       [ 'label' => 'None', 'value' => '' ],
     *       [ 'label' => 'Abc', 'value'  => '' ],
     *       [ 'label' => 'Def', 'value'  => '15' ],
     * ]
     */
    private function buildValuesGroupByLabel(GraphOnTrackersV5_Engine_Bar $engine, array $source_values): array
    {
        $grouped_source_values = [];
        foreach ($engine->legend as $legend_key => $legend_label) {
            $grouped_source_values[] = [
                'label' => $legend_label,
                'value' => $source_values[$legend_key] ?? ''
            ];
        }
        return $grouped_source_values;
    }

    /**
    * will return:
    * [
    *   'label'  => '18-19 July',
    *   'values' => [
    *       [ 'label' => 'None', 'value' => '' ],
    *       [ 'label' => 'Abc', 'value' => '' ],
    *       [ 'label' => 'Def', 'value' => 15 ],
    *    ]
    * ]
    */
    private function buildGroupByFieldValues(
        GraphOnTrackersV5_Engine_Bar $engine,
        string $source_key,
        array $grouped_source_values,
        array $values
    ): array {
        $values[$this->getXaxisKeyFromLabel($engine, $source_key)] = [
            'label' => $engine->xaxis[$source_key],
            'values' => $grouped_source_values,
        ];
        return $values;
    }

    /**
     * $engine->xaxis  = [
     *      ''    => 'None',
     *      '100' => '27-28 April',
     *      '101' => '3-4 May',
     *      '102' => '1-2 June',
     *      '103' => '18-19 July'
     *];
     */
    private function getXaxisKeyFromLabel(GraphOnTrackersV5_Engine_Bar $engine, string $source_key): string
    {
        $key = array_search($engine->xaxis[$source_key], $engine->xaxis);
        if ($key === false) {
            throw new \LogicException("Try to access to an unknown key " . $source_key);
        }
        return (string) $key;
    }

    private function reorderKeyRespectingXaxisOrder(GraphOnTrackersV5_Engine_Bar $engine, array $values): array
    {
        $ordered_values = [];
        foreach ($engine->xaxis as $ordered_key => $ordered_label) {
            $ordered_values[] = $values[$ordered_key];
        }

        return $ordered_values;
    }
}
