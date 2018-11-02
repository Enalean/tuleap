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

namespace Tuleap\CrossTracker\Report\CSV;

use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldCollection;
use Tuleap\Tracker\REST\v1\ArtifactMatchingReportCollection;

class CSVRepresentationFactory
{
    /**
     * @var CSVRepresentationBuilder
     */
    private $csv_builder;

    public function __construct(CSVRepresentationBuilder $csv_builder)
    {
        $this->csv_builder = $csv_builder;
    }

    public function buildRepresentations(
        ArtifactMatchingReportCollection $collection,
        \PFUser $current_user,
        SimilarFieldCollection $similar_fields
    ) {
        $representations = [$this->csv_builder->buildHeaderLine($current_user, $similar_fields)];

        foreach ($collection->getArtifacts() as $artifact) {
            if (! $artifact->userCanView($current_user)) {
                continue;
            }

            $representations[] = $this->csv_builder->build($artifact, $current_user, $similar_fields);
        }

        return new PaginatedCollectionOfCSVRepresentations($representations, $collection->getTotalSize());
    }
}
