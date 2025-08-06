<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata;

use Tuleap\CrossTracker\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Query\Advanced\InvalidComparisonCollectorParameters;
use Tuleap\CrossTracker\Query\Advanced\InvalidOrderByBuilderParameters;
use Tuleap\CrossTracker\Query\Advanced\InvalidOrderByListChecker;
use Tuleap\CrossTracker\Query\Advanced\InvalidSelectableCollectorParameters;
use Tuleap\CrossTracker\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\InvalidOrderBy;

final readonly class MetadataChecker
{
    public function __construct(
        private InvalidMetadataChecker $comparison_checker,
        private InvalidOrderByListChecker $order_by_list_checker,
    ) {
    }

    /**
     * @throws InvalidQueryException
     */
    public function checkMetadataIsValidForSearch(
        Metadata $metadata,
        Comparison $comparison,
        InvalidComparisonCollectorParameters $collector_parameters,
    ): void {
        if (! in_array($metadata->getName(), AllowedMetadata::SEARCHABLE_NAMES, true)) {
            $collector_parameters->getInvalidSearchablesCollection()->addNonexistentSearchable($metadata->getName());
            return;
        }

        $this->comparison_checker->checkComparisonIsValid($metadata, $comparison);
    }

    public function checkMetadataIsValidForSelect(
        Metadata $metadata,
        InvalidSelectableCollectorParameters $collector_parameters,
    ): void {
        if (! in_array($metadata->getName(), AllowedMetadata::SELECTABLE_NAMES, true)) {
            $collector_parameters->invalid_selectables_collection->addNonExistentSelectable($metadata->getName());
        }
    }

    /**
     * @throws InvalidQueryException
     */
    public function checkMetadataIsValidForOrderBy(
        Metadata $metadata,
        InvalidOrderByBuilderParameters $parameters,
    ): void {
        if (! in_array($metadata->getName(), AllowedMetadata::SORTABLE_NAMES, true)) {
            $parameters->setInvalidOrderBy(new InvalidOrderBy(
                sprintf('Sorting artifacts by %s is not allowed. Please refine your query or check the configuration of the trackers.', $metadata->getName()),
                sprintf(
                    dgettext('tuleap-crosstracker', 'Sorting artifacts by %s is not allowed. Please refine your query or check the configuration of the trackers.'),
                    $metadata->getName(),
                ),
            ));
            return;
        }

        if (
            ($metadata->getName() === AllowedMetadata::STATUS || $metadata->getName() === AllowedMetadata::ASSIGNED_TO)
            && ! $this->order_by_list_checker->metadataListIsSortable($metadata, $parameters->trackers)
        ) {
            $parameters->setInvalidOrderBy(new InvalidOrderBy(
                sprintf('%s is a list with multiple values, sorting artifacts by it is not allowed. Please refine your query or check the configuration of the trackers.', $metadata->getName()),
                sprintf(
                    dgettext('tuleap-crosstracker', '%s is a list with multiple values, sorting artifacts by it is not allowed. Please refine your query or check the configuration of the trackers.'),
                    $metadata->getName(),
                ),
            ));
        }
    }
}
