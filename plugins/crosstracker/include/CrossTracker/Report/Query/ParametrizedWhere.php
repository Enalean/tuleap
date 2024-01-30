<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

/**
 * @psalm-immutable
 */
final class ParametrizedWhere implements IProvideParametrizedFromAndWhereSQLFragments
{
    /**
     * @psalm-param list<float|int|string> $parameters
     */
    public function __construct(
        private readonly string|EasyStatement $where,
        private readonly array $parameters,
    ) {
    }

    public function getWhere(): string|EasyStatement
    {
        return $this->where;
    }

    public function getWhereParameters(): array
    {
        return $this->parameters;
    }

    public function getFrom(): string
    {
        return '';
    }

    public function getFromParameters(): array
    {
        return [];
    }

    public function getAllParametrizedFrom(): array
    {
        return [];
    }
}
