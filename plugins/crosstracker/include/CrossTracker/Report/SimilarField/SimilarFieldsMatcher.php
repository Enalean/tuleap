<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\SimilarField;

use Tuleap\CrossTracker\CrossTrackerReport;

class SimilarFieldsMatcher
{
    /** @var SupportedFieldsDao */
    private $similar_fields_dao;
    /** @var \Tracker_FormElementFactory */
    private $form_element_factory;

    public function __construct(SupportedFieldsDao $similar_fields_dao, \Tracker_FormElementFactory $form_element_factory)
    {
        $this->similar_fields_dao   = $similar_fields_dao;
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @param CrossTrackerReport $report
     * @return SimilarFieldCollection
     */
    public function getSimilarFieldsCollection(CrossTrackerReport $report)
    {
        $rows = $this->similar_fields_dao->searchByTrackerIds($report->getTrackerIds());

        $filtered_rows = $this->filterRowsWithLessThanTwoTrackers($rows);

        $collection = new SimilarFieldCollection([]);
        foreach ($filtered_rows as $row) {
            $field = $this->form_element_factory->getFormElementFieldById($row['field_id']);
            $collection->addField($field, $row['tracker_id']);
        }

        return $collection;
    }

    /**
     * @param mixed[] $rows
     * @return mixed[]
     */
    private function filterRowsWithLessThanTwoTrackers($rows)
    {
        $count_of_trackers = $this->countTrackersWithSameNameAndSameTypeFields($rows);
        return array_filter(
            $rows,
            function ($row) use ($count_of_trackers) {
                $count_key = $this->getCountKey($row);
                return $count_of_trackers[$count_key] > 1;
            }
        );
    }

    /**
     * @param mixed[] $rows
     * @return int[]
     */
    private function countTrackersWithSameNameAndSameTypeFields($rows)
    {
        return array_reduce(
            $rows,
            function ($accumulator, $row) {
                $count_key = $this->getCountKey($row);
                if (! isset($accumulator[$count_key])) {
                    $accumulator[$count_key] = 1;
                } else {
                    $accumulator[$count_key]++;
                }

                return $accumulator;
            },
            []
        );
    }

    private function getCountKey($row)
    {
        $field_name = $row['name'];
        $field_type = $row['type'];
        return $field_type . '/' . $field_name;
    }
}
