<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query;

abstract class ParametrizedBase implements IProvideParametrizedFromAndWhereSQLFragments
{
    public function getFrom(): string
    {
        $unique_parametrized_from = array_unique($this->getAllParametrizedFrom());

        return implode(
            ' ',
            array_map(
                function (ParametrizedFrom $parametrized_from) {
                    return $parametrized_from->getFrom();
                },
                $unique_parametrized_from
            )
        );
    }

    public function getFromParameters(): array
    {
        $unique_parametrized_from = array_unique($this->getAllParametrizedFrom());

        return array_reduce(
            $unique_parametrized_from,
            function (array $carry, ParametrizedFrom $parametrized_from) {
                return array_merge($carry, $parametrized_from->getParameters());
            },
            []
        );
    }
}
