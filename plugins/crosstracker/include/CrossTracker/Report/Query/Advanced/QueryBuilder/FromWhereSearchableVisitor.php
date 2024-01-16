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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder;

use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SearchableVisitor;

/**
 * @template-implements SearchableVisitor<FromWhereSearchableVisitorParameters, IProvideParametrizedFromAndWhereSQLFragments>
 */
final class FromWhereSearchableVisitor implements SearchableVisitor
{
    public function visitField(Field $field, $parameters)
    {
        return $parameters->getFieldFromWhereBuilder()->getFromWhere(
            $field,
            $parameters->getComparison(),
            $parameters->getTrackers()
        );
    }

    public function visitMetaData(Metadata $metadata, $parameters)
    {
        return $parameters->getMetadataFromWhereBuilder()->getFromWhere(
            $metadata,
            $parameters->getComparison(),
            $parameters->getTrackers()
        );
    }
}
