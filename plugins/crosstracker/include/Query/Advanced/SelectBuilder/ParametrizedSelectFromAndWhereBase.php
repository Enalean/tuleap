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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder;

use Override;
use Tuleap\Option\Option;
use function Psl\Type\string;

final class ParametrizedSelectFromAndWhereBase implements IProvideParametrizedSelectAndFromAndWhereSQLFragments
{
    /**
     * @var string[]
     */
    private array $from             = [];
    private array $from_parameters  = [];
    private array $where_parameters = [];
    /**
     * @var string[]
     */
    private array $select = [];


    #[Override]
    final public function getFrom(): string
    {
        return implode("\n", $this->from);
    }

    #[Override]
    final public function getFromParameters(): array
    {
        return $this->from_parameters;
    }

    #[Override]
    final public function getSelect(): string
    {
        return implode(', ', $this->select);
    }

    #[Override]
    public function getWhere(): Option
    {
        return Option::nothing(string());
    }

    #[Override]
    public function getWhereParameters(): array
    {
        return $this->where_parameters;
    }

    public function addSelect(string $select): void
    {
        if ($select !== '') {
            $this->select[] = $select;
        }
    }

    public function addFrom(string $from, array $parameters): void
    {
        if ($from !== '') {
            $this->from[]          = $from;
            $this->from_parameters = [
                ...$this->from_parameters,
                ...$parameters,
            ];
        }
    }
}
