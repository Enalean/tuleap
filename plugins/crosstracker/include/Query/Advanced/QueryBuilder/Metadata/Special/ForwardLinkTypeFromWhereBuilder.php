<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\Special;

use LogicException;
use Tuleap\CrossTracker\Query\Advanced\QueryBuilder\Metadata\MetadataValueWrapperParameters;
use Tuleap\CrossTracker\Query\ParametrizedWhere;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, ParametrizedWhere>
 */
final readonly class ForwardLinkTypeFromWhereBuilder implements ValueWrapperVisitor
{
    public function getFromWhere(MetadataValueWrapperParameters $parameters): IProvideParametrizedFromAndWhereSQLFragments
    {
        return $parameters->comparison->getValueWrapper()->accept($this, $parameters);
    }

    #[\Override]
    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to link type is not implemented yet');
    }

    #[\Override]
    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to link type is not implemented yet');
    }

    #[\Override]
    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current date time should have been flagged as invalid for Link Type');
    }

    #[\Override]
    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison with Between() should have been flagged as invalid for Link Type');
    }

    #[\Override]
    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new LogicException('Comparison with In() should have been flagged as invalid for Link Type');
    }

    #[\Override]
    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new LogicException('Comparison to current user should have been flagged as invalid for Link Type');
    }
}
