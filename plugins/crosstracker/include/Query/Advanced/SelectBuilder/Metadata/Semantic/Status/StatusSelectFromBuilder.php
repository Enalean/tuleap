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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Status;

use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;

final class StatusSelectFromBuilder
{
    public function getSelectFrom(): IProvideParametrizedSelectAndFromSQLFragments
    {
        $select = "status.label AS '@status', status_decorator.tlp_color_name AS '@status_color'";
        $from   = <<<EOSQL
        LEFT JOIN tracker_semantic_status AS status_semantic ON (status_semantic.tracker_id = artifact.tracker_id)
        LEFT JOIN tracker_field AS status_field ON (status_field.id = status_semantic.field_id)
        LEFT JOIN tracker_changeset_value AS status_tcv
            ON (status_tcv.changeset_id = changeset.id AND status_tcv.field_id = status_field.id)
        LEFT JOIN tracker_changeset_value_list AS status_value ON (status_value.changeset_value_id = status_tcv.id)
        LEFT JOIN tracker_field_list_bind_static_value AS status ON (status.id = status_value.bindvalue_id)
        LEFT JOIN tracker_field_list_bind_decorator AS status_decorator ON (status.id = status_decorator.value_id)
        EOSQL;

        return new ParametrizedSelectFrom($select, $from, []);
    }
}
