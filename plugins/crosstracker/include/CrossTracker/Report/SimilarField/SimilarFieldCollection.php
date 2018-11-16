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

use Tracker_Artifact;
use Tracker_FormElement_Field;

class SimilarFieldCollection implements \IteratorAggregate
{
    /**
     * @var SimilarFieldCandidate[]
     */
    private $similar_candidates;
    /**
     * @var Tracker_FormElement_Field[]
     */
    private $similar_fields_sorted_by_name_and_tracker_id;

    public function __construct(SimilarFieldCandidate ...$candidates)
    {
        $this->similar_candidates = $this->filterCandidatesWithLessThanTwoTrackers(...$candidates);
    }

    /**
     * @return SimilarFieldCandidate[]
     */
    private function filterCandidatesWithLessThanTwoTrackers(SimilarFieldCandidate ...$candidates)
    {
        $count_of_trackers = $this->countTrackersWithSameNameAndSameTypeFields(...$candidates);
        return array_filter(
            $candidates,
            function (SimilarFieldCandidate $candidate) use ($count_of_trackers) {
                return $count_of_trackers[$candidate->getIdentifier()] > 1;
            }
        );
    }

    /**
     * @return int[]
     */
    private function countTrackersWithSameNameAndSameTypeFields(SimilarFieldCandidate ...$candidates)
    {
        return array_reduce(
            $candidates,
            function ($accumulator, SimilarFieldCandidate $candidate) {
                $count_key = $candidate->getIdentifier();
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

    /**
     * @return string[]
     */
    public function getFieldNames()
    {
        $this->sortSimilarFieldsByNameAndTrackerIDIfNeeded();
        return array_keys($this->similar_fields_sorted_by_name_and_tracker_id);
    }

    /**
     * @param Tracker_Artifact $artifact
     * @param string           $field_name
     * @return Tracker_FormElement_Field|null
     */
    public function getField(Tracker_Artifact $artifact, $field_name)
    {
        $this->sortSimilarFieldsByNameAndTrackerIDIfNeeded();
        if (! isset($this->similar_fields_sorted_by_name_and_tracker_id[$field_name][$artifact->getTrackerId()])) {
            return null;
        }
        return $this->similar_fields_sorted_by_name_and_tracker_id[$field_name][$artifact->getTrackerId()];
    }

    private function sortSimilarFieldsByNameAndTrackerIDIfNeeded()
    {
        if ($this->similar_fields_sorted_by_name_and_tracker_id !== null) {
            return;
        }
        $this->similar_fields_sorted_by_name_and_tracker_id = [];
        foreach ($this->similar_candidates as $similar_field) {
            $field      = $similar_field->getField();
            $field_name = $field->getName();
            if (! isset($this->similar_fields_sorted_by_name_and_tracker_id[$field_name])) {
                $this->similar_fields_sorted_by_name_and_tracker_id[$field_name] = [];
            }
            $this->similar_fields_sorted_by_name_and_tracker_id[$field_name][$field->getTrackerId()] = $field;
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->similar_candidates);
    }
}
