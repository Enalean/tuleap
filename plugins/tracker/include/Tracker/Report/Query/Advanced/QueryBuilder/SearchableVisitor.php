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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tracker;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Visitor;

class SearchableVisitor implements Visitor
{
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    public function visitField(Field $field, SearchableVisitorParameter $parameters)
    {
        $formelement = $this->getFormElementFromComparison($parameters->getComparison(), $parameters->getTracker());

        return $parameters->getFieldComparisonVisitor()
            ->getFromWhereBuilder($formelement)
            ->getFromWhere($parameters->getComparison(), $formelement);
    }

    private function getFormElementFromComparison(Comparison $comparison, Tracker $tracker)
    {
        $formelement = $this->form_element_factory->getUsedFieldByName(
            $tracker->getId(),
            $comparison->getSearchable()->getName()
        );

        return $formelement;
    }

    public function visitMetaData(Metadata $metadata, SearchableVisitorParameter $parameters)
    {
        return $parameters->getMetadataComparisonFromWhereBuilder()->getFromWhere($parameters->getComparison());
    }
}
