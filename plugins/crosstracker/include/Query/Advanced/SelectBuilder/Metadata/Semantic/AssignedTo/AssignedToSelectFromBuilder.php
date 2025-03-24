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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\AssignedTo;

use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;

final class AssignedToSelectFromBuilder
{
    public function getSelectFrom(): IProvideParametrizedSelectAndFromSQLFragments
    {
        $select = "contributor.bindvalue_id AS '@assigned_to'";
        $from   = <<<EOSQL
        LEFT JOIN tracker_semantic_contributor AS contributor_semantic ON (contributor_semantic.tracker_id = artifact.tracker_id)
        LEFT JOIN tracker_field AS contributor_field ON (contributor_field.id = contributor_semantic.field_id)
        LEFT JOIN tracker_changeset_value AS contributor_tcv
            ON (contributor_tcv.changeset_id = changeset.id AND contributor_tcv.field_id = contributor_field.id)
        LEFT JOIN tracker_changeset_value_list AS contributor ON (contributor.changeset_value_id = contributor_tcv.id)
        EOSQL;

        return new ParametrizedSelectFrom($select, $from, []);
    }
}
