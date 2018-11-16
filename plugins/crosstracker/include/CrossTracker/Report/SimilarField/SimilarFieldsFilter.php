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

class SimilarFieldsFilter
{
    /**
     * @return SimilarFieldCandidate[]
     */
    public function filterCandidatesUsedInSemantics(SimilarFieldCandidate ...$candidates)
    {
        return array_filter(
            $candidates,
            function (SimilarFieldCandidate $candidate) {
                $tracker = $candidate->getTracker();
                $title_semantic = \Tracker_Semantic_Title::load($tracker);
                $description_semantic = \Tracker_Semantic_Description::load($tracker);
                $status_semantic = \Tracker_Semantic_Status::load($tracker);

                $semantics_usage_visitor = new FieldUsedInSupportedSemanticsVisitor(
                    $title_semantic,
                    $description_semantic,
                    $status_semantic
                );

                return ! $candidate->getField()->accept($semantics_usage_visitor);
            }
        );
    }
}
