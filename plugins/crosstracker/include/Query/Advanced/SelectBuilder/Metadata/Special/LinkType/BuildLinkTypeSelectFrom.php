<?php
/**
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


namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\LinkType;

use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromAndWhereSQLFragments;
use Tuleap\Option\Option;

interface BuildLinkTypeSelectFrom
{
    /**
     * source artifact id for forward links
     * target artifact id for reverse links
     * @param Option<int> $artifact_id
     * @param array<int> $artifact_ids
     */
    public function getSelectFrom(Option $artifact_id, array $artifact_ids): IProvideParametrizedSelectAndFromAndWhereSQLFragments;
}
