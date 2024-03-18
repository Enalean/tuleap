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

namespace Tuleap\CrossTracker\Report\Query\Advanced;

use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldNotFoundInAnyTrackerFault;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeIsNotSupportedFault;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\DuckTypedField\DuckTypedFieldChecker;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata\CheckMetadataUsage;
use Tuleap\NeverThrow\Fault;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SearchableVisitor;

/**
 * @template-implements SearchableVisitor<InvalidSearchableCollectorParameters, void>
 */
final class InvalidSearchableCollectorVisitor implements SearchableVisitor
{
    public function __construct(
        private readonly CheckMetadataUsage $metadata_checker,
        private readonly DuckTypedFieldChecker $field_checker,
    ) {
    }

    public function visitField(Field $searchable_field, $parameters)
    {
        $this->field_checker->checkFieldIsValid($searchable_field, $parameters)
            ->match(
                static function () {
                    // Do nothing
                },
                static function (Fault $fault) use ($searchable_field, $parameters) {
                    $invalid_searchables_collection = $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection();
                    if ($fault instanceof FieldTypeIsNotSupportedFault || $fault instanceof FieldNotFoundInAnyTrackerFault) {
                        $invalid_searchables_collection->addNonExistentSearchable($searchable_field->getName());
                        return;
                    }
                    $invalid_searchables_collection->addInvalidSearchableError((string) $fault);
                }
            );
    }

    public function visitMetadata(Metadata $metadata, $parameters)
    {
        $invalid_searchables_collection = $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection();
        if (! in_array($metadata->getName(), AllowedMetadata::NAMES, true)) {
            $invalid_searchables_collection->addNonexistentSearchable(
                $metadata->getName()
            );

            return;
        }

        try {
            $this->metadata_checker->checkMetadataIsValid(
                $metadata,
                $parameters->getComparison(),
                $parameters->getInvalidSearchablesCollectorParameters(),
                $parameters->getComparisonChecker()
            );
        } catch (InvalidQueryException $exception) {
            $invalid_searchables_collection->addInvalidSearchableError(
                $exception->getMessage()
            );
        }
    }
}
