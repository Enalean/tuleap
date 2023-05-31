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

use Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\InvalidQueryException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SearchableVisitor;

/**
 * @template-implements SearchableVisitor<InvalidSearchableCollectorParameters, void>
 */
class InvalidSearchableCollectorVisitor implements SearchableVisitor
{
    public function visitField(Field $searchable_field, $parameters)
    {
        $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addNonexistentSearchable(
            $searchable_field->getName()
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
            $parameters->getMetadataChecker()->checkMetadataIsValid(
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
