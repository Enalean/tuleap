<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\LinkType;

use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFromAndWhere;
use Tuleap\Option\Option;
use function Psl\Type\string;

/**
 * @psalm-immutable
 */
final readonly class WithoutLinkTypeSelectFromBuilder implements BuildLinkTypeSelectFrom
{
    #[\Override]
    public function getSelectFrom(Option $artifact_id, array $artifact_ids): IProvideParametrizedSelectAndFromAndWhereSQLFragments
    {
        return new ParametrizedSelectFromAndWhere('', '', [], Option::nothing(string()), []);
    }
}
