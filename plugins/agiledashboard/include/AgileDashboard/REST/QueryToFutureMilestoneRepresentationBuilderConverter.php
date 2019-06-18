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

use Tuleap\AgileDashboard\Milestone\FutureMilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\Milestone\MilestoneRepresentationBuilderInterface;

class QueryToFutureMilestoneRepresentationBuilderConverter
{

    private const FUTURE = 'future';
    /**
     * @var FutureMilestoneRepresentationBuilder
     */
    private $builder;

    public function __construct(FutureMilestoneRepresentationBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param string $query
     * @return MilestoneRepresentationBuilderInterface
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
            return $this->builder;
        }

        throw MalformedQueryParameterException::invalidQueryPeriodParameter();
    }
}
