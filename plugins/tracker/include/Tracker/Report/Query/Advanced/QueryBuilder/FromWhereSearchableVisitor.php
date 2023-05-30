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

use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SearchableVisitor;
use Tuleap\Tracker\Report\Query\IProvideFromAndWhereSQLFragments;

/**
 * @template-implements SearchableVisitor<FromWhereSearchableVisitorParameter, IProvideFromAndWhereSQLFragments>
 */
class FromWhereSearchableVisitor implements SearchableVisitor
{
    public function __construct(private readonly Tracker_FormElementFactory $form_element_factory)
    {
    }

    public function visitField(Field $field, $parameters)
    {
        $formelement = $this->getFormElementFromComparison($parameters->getComparison(), $parameters->getTracker());

        return $parameters->getFieldComparisonVisitor()
            ->getFromWhereBuilder($formelement)
            ->getFromWhere($parameters->getComparison(), $formelement);
    }

    private function getFormElementFromComparison(Comparison $comparison, Tracker $tracker): \Tracker_FormElement_Field
    {
        $name        = $comparison->getSearchable()->getName();
        $formelement = $this->form_element_factory->getUsedFieldByName($tracker->getId(), $name);

        if (! $formelement) {
            throw new \Exception(sprintf("Field %s cannot be found", $name));
        }

        return $formelement;
    }

    public function visitMetaData(Metadata $metadata, $parameters)
    {
        return $parameters->getMetadataComparisonFromWhereBuilder()->getFromWhere($metadata, $parameters->getComparison());
    }
}
