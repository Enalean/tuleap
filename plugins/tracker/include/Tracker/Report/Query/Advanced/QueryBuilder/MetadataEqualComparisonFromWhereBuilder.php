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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\StatusOpenValueWrapper;
use Tuleap\Tracker\Report\Query\CommentFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentDateTimeValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\CurrentUserValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\InValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\MetadataValueWrapperParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ValueWrapperVisitor;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

/**
 * @template-implements ValueWrapperVisitor<MetadataValueWrapperParameters, string>
 */
class MetadataEqualComparisonFromWhereBuilder implements MetadataComparisonFromWhereBuilder, ValueWrapperVisitor
{
    public function __construct(private readonly CommentFromWhereBuilder $comment_from_where_builder)
    {
    }

    public function getFromWhere(Metadata $metadata, Comparison $comparison): IProvideFromAndWhereSQLFragments
    {
        $value = $comparison->getValueWrapper()->accept($this, new MetadataValueWrapperParameters($metadata));

        if ($value === '') {
            return $this->searchArtifactsWithoutComment($comparison);
        }

        return $this->searchComment($comparison, $value);
    }

    public function visitCurrentDateTimeValueWrapper(CurrentDateTimeValueWrapper $value_wrapper, $parameters)
    {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitSimpleValueWrapper(SimpleValueWrapper $value_wrapper, $parameters)
    {
        return (string) $value_wrapper->getValue();
    }

    public function visitBetweenValueWrapper(BetweenValueWrapper $value_wrapper, $parameters)
    {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitInValueWrapper(InValueWrapper $collection_of_value_wrappers, $parameters)
    {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitCurrentUserValueWrapper(CurrentUserValueWrapper $value_wrapper, $parameters)
    {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    public function visitStatusOpenValueWrapper(StatusOpenValueWrapper $value_wrapper, $parameters)
    {
        throw new \RuntimeException("Metadata is not supported here.");
    }

    private function searchComment(Comparison $comparison, $value): IProvideFromAndWhereSQLFragments
    {
        $suffix = spl_object_hash($comparison);

        return $this->comment_from_where_builder->getFromWhereWithComment($value, $suffix);
    }

    private function searchArtifactsWithoutComment(Comparison $comparison): IProvideFromAndWhereSQLFragments
    {
        $suffix = spl_object_hash($comparison);

        return $this->comment_from_where_builder->getFromWhereWithoutComment($suffix);
    }
}
