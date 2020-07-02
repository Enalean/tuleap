<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\GraphOnTrackersV5\DataTransformation;

use ColorHelper;

class CumulativeFlowColumnBuilder
{
    /**
     * @var CumulativeFlowDAO
     */
    private $dao;

    public function __construct(CumulativeFlowDAO $dao)
    {
        $this->dao = $dao;
    }

    public static function build(): self
    {
        return new self(new CumulativeFlowDAO());
    }

    public function initEmptyColumns(
        int $field_id,
        int $start_date,
        int $nb_steps,
        array $time_filler,
        int $scale
    ): array {
        $chart_headers_with_color = $this->dao->getChartColors($field_id);
        $none_color               = $this->dao->getColorOfNone($field_id);

        $result_array = [];

        $result_array[\Tracker_FormElement_Field_List::NONE_VALUE] = [
            'id'     => \Tracker_FormElement_Field_List::NONE_VALUE,
            'label'  => $GLOBALS['Language']->getText('global', 'none'),
            'color'  => $this->getColumnColor($none_color),
            'values' => $this->generateEmptyValues($start_date, $nb_steps, $time_filler, $scale)
        ];

        foreach ($chart_headers_with_color as $data) {
            $column = [
                'id'     => (int) $data['id'],
                'label'  => $data['label'],
                'color'  => $this->getColumnColor($data),
                'values' => $this->generateEmptyValues($start_date, $nb_steps, $time_filler, $scale)
            ];

            $result_array[(int) $data['id']] = $column;
        }

        foreach ($result_array as $timestamp => $values) {
            $result_array[$timestamp] = array_reverse($result_array[$timestamp], true);
        }

        return $result_array;
    }

    private function getColumnColor(?array $data): ?string
    {
        if (isset($data['tlp_color_name'])) {
            return $data['tlp_color_name'];
        }
        if (isset($data['red'], $data['green'], $data['blue'])) {
            return ColorHelper::RGBToHexa($data['red'], $data['green'], $data['blue']);
        }

        return null;
    }

    private function generateEmptyValues(
        int $start_date,
        int $nb_steps,
        array $time_filler,
        int $scale
    ): array {
        $values = [];
        for ($i = 0; $i <= $nb_steps; $i++) {
            if (! isset($time_filler[$scale])) {
                continue;
            }
            $timestamp          = $start_date + ($i * $time_filler[$scale]);
            $values[$timestamp] = [
                'date'  => $timestamp,
                'count' => 0
            ];
        }

        return $values;
    }
}
