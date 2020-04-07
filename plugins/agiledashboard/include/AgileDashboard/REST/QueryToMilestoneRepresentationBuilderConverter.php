<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST;

use AgileDashboard_Milestone_MilestoneRepresentationBuilder;
use stdClass;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\MilestoneRepresentationBuilderInterface;
use Tuleap\AgileDashboard\Milestone\StatusMilestoneRepresentationBuilder;

class QueryToMilestoneRepresentationBuilderConverter
{
    /** @var QueryToPeriodMilestoneRepresentationBuilderConverter */
    private $period_converter;

    /** @var QueryToCriterionStatusConverter */
    private $status_converter;
    /**
     * @var AgileDashboard_Milestone_MilestoneRepresentationBuilder
     */
    private $milestone_representation_builder;

    public function __construct(
        AgileDashboard_Milestone_MilestoneRepresentationBuilder $milestone_representation_builder,
        QueryToPeriodMilestoneRepresentationBuilderConverter $period_converter,
        QueryToCriterionStatusConverter $status_converter
    ) {
        $this->milestone_representation_builder = $milestone_representation_builder;
        $this->period_converter                 = $period_converter;
        $this->status_converter                 = $status_converter;
    }

    /**
     * @throws MalformedQueryParameterException
     */
    public function convert(string $query): MilestoneRepresentationBuilderInterface
    {
        if ($query === '') {
            return new StatusMilestoneRepresentationBuilder(
                $this->milestone_representation_builder,
                new StatusAll()
            );
        }

        $query_object = json_decode(stripslashes($query));

        if ($query_object === null) {
            throw MalformedQueryParameterException::invalidQueryParameter();
        }

        if ($query_object == new stdClass()) {
            return new StatusMilestoneRepresentationBuilder(
                $this->milestone_representation_builder,
                new StatusAll()
            );
        }

        if (!isset($query_object->period) && !isset($query_object->status)) {
            throw MalformedQueryParameterException::invalidQueryParameter();
        }

        if (isset($query_object->period, $query_object->status)) {
            throw MalformedQueryParameterException::invalidQueryParameter();
        }

        if (isset($query_object->period)) {
            return $this->period_converter->convert($query);
        }

        return new StatusMilestoneRepresentationBuilder(
            $this->milestone_representation_builder,
            $this->status_converter->convert($query)
        );
    }
}
