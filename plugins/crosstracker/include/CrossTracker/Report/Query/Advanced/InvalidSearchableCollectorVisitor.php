<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSemantic\InvalidSemanticComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;

class InvalidSearchableCollectorVisitor implements Visitor
{
    public function visitField(
        Field $searchable_field,
        InvalidSearchableCollectorParameters $parameters
    ) {
        $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addNonexistentSearchable(
            $searchable_field->getName()
        );
    }

    public function visitMetadata(
        Metadata $metadata,
        InvalidSearchableCollectorParameters $parameters
    ) {
        if (! in_array($metadata->getName(), AllowedMetadata::$NAMES, true)) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addNonexistentSearchable(
                $metadata->getName()
            );

            return;
        }

        try {
            $parameters->getSemanticFieldChecker()->checkSemanticMetadataIsValid($metadata, $parameters->getComparison());
        } catch (InvalidSemanticComparisonException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                $exception->getMessage()
            );
        }
    }
}
