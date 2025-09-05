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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SearchableVisitor;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

/**
 * @template-implements SearchableVisitor<FromWhereSearchableVisitorParameters, IProvideParametrizedFromAndWhereSQLFragments>
 */
final class FromWhereSearchableVisitor implements SearchableVisitor
{
    #[\Override]
    public function visitField(Field $field, $parameters)
    {
        return $parameters->field_from_where_builder->getFromWhere(
            $field,
            $parameters->comparison,
            $parameters->user,
            $parameters->trackers
        );
    }

    #[\Override]
    public function visitMetaData(Metadata $metadata, $parameters)
    {
        return $parameters->metadata_from_where_builder->getFromWhere(
            $metadata,
            $parameters->comparison,
            $parameters->trackers,
            $parameters->user,
        );
    }
}
