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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\ProjectName;

use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;

final class ProjectNameSelectFromBuilder
{
    public function getSelectFrom(): IProvideParametrizedSelectAndFromSQLFragments
    {
        $select = "project.group_name AS '@project.name', project.icon_codepoint AS '@project.icon'";
        $from   = <<<EOSQL
        LEFT JOIN `groups` AS project ON (project.group_id = tracker.group_id)
        EOSQL;

        return new ParametrizedSelectFrom($select, $from, []);
    }
}
