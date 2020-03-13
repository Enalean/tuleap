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

use Tuleap\AgileDashboard\Milestone\CurrentMilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\Milestone\FutureMilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\Milestone\MilestoneRepresentationBuilderInterface;

class QueryToPeriodMilestoneRepresentationBuilderConverter
{

    private const FUTURE = 'future';
    private const CURRENT = 'current';
    /**
     * @var FutureMilestoneRepresentationBuilder
     */
    private $future_builder;
    /**
     * @var CurrentMilestoneRepresentationBuilder
     */
    private $current_builder;

    public function __construct(
        FutureMilestoneRepresentationBuilder $future_builder,
        CurrentMilestoneRepresentationBuilder $current_builder
    ) {
        $this->future_builder  = $future_builder;
        $this->current_builder = $current_builder;
    }

    /**
     * @throws MalformedQueryParameterException
     */
    public function convert(string $query): MilestoneRepresentationBuilderInterface
    {
        $query_object = json_decode(stripslashes($query));

        if ($query_object === null) {
            throw MalformedQueryParameterException::invalidQueryPeriodParameter();
        }

        if (!isset($query_object->period)) {
            throw MalformedQueryParameterException::invalidQueryPeriodParameter();
        }

        if ($query_object->period === self::FUTURE) {
            return $this->future_builder;
        }

        if ($query_object->period === self::CURRENT) {
            return $this->current_builder;
        }

        throw MalformedQueryParameterException::invalidQueryPeriodParameter();
    }
}
