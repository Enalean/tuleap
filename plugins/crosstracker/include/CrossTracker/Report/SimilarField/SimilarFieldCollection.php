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
    private $candidates_sorted_by_identifier_and_tracker_id;

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
                return $count_of_trackers[$candidate->getTypeWithBind()] > 1;
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
                $count_key = $candidate->getTypeWithBind();
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
        $this->sortSimilarFieldsByIdentifierAndTrackerIDIfNeeded();

        $identifier_strings = array_keys($this->candidates_sorted_by_identifier_and_tracker_id);
        return array_map(function ($identifier_string) {
            $identifier = SimilarFieldIdentifier::buildFromIdentifierString($identifier_string);
            return $identifier->getLabel();
        }, $identifier_strings);
    }

    /**
     * @return SimilarFieldIdentifier[]
     */
    public function getFieldIdentifiers()
    {
        $this->sortSimilarFieldsByIdentifierAndTrackerIDIfNeeded();

        $identifier_strings = array_keys($this->candidates_sorted_by_identifier_and_tracker_id);
        return array_map(function ($identifier_string) {
            return SimilarFieldIdentifier::buildFromIdentifierString($identifier_string);
        }, $identifier_strings);
    }

    /**
     * @return Tracker_FormElement_Field|null
     */
    public function getField(Tracker_Artifact $artifact, SimilarFieldIdentifier $identifier)
    {
        $this->sortSimilarFieldsByIdentifierAndTrackerIDIfNeeded();
        $identifier_string = $identifier->getIdentifierWithBindType();
        if (
            ! isset(
                $this->candidates_sorted_by_identifier_and_tracker_id[$identifier_string][$artifact->getTrackerId()]
            )
        ) {
            return null;
        }
        return $this->candidates_sorted_by_identifier_and_tracker_id[$identifier_string][$artifact->getTrackerId()];
    }

    private function sortSimilarFieldsByIdentifierAndTrackerIDIfNeeded()
    {
        if ($this->candidates_sorted_by_identifier_and_tracker_id !== null) {
            return;
        }
        $this->candidates_sorted_by_identifier_and_tracker_id = [];
        foreach ($this->similar_candidates as $similar_field) {
            $field             = $similar_field->getField();
            $identifier_string = $similar_field->getIdentifierWithBindType();
            if (! isset($this->candidates_sorted_by_identifier_and_tracker_id[$identifier_string])) {
                $this->candidates_sorted_by_identifier_and_tracker_id[$identifier_string] = [];
            }
            $this->candidates_sorted_by_identifier_and_tracker_id[$identifier_string][$field->getTrackerId()] = $field;
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->similar_candidates);
    }
}
