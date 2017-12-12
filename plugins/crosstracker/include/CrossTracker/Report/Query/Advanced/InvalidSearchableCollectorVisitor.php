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

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidMetadata\InvalidMetadataForComparisonException;

class InvalidSearchableCollectorVisitor implements Visitor
{
    const SUPPORTED_METADATA_NAME = '@title';

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
        if ($metadata->getName() !== self::SUPPORTED_METADATA_NAME) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addNonexistentSearchable(
                $metadata->getName()
            );

            return;
        }

        try {
            $parameters->getSemanticFieldChecker()->checkSemanticMetadataIsValid($parameters->getComparison());
        } catch (InvalidMetadataForComparisonException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                $exception->getMessage()
            );
        } catch (InvalidFieldException $exception) {
            $parameters->getInvalidSearchablesCollectorParameters()->getInvalidSearchablesCollection()->addInvalidSearchableError(
                $exception->getMessage()
            );
        }
    }
}
