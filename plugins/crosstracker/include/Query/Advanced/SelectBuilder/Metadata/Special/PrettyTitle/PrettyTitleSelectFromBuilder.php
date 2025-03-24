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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\PrettyTitle;

use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;

final class PrettyTitleSelectFromBuilder
{
    public function getSelectFrom(): IProvideParametrizedSelectAndFromSQLFragments
    {
        $select = "tracker.item_name AS '@pretty_title.tracker', tracker.color AS '@pretty_title.color', pretty_title.value AS '@pretty_title'";
        $from   = <<<EOSQL
        LEFT JOIN tracker_semantic_title AS pretty_title_semantic ON (pretty_title_semantic.tracker_id = artifact.tracker_id)
        LEFT JOIN tracker_field AS pretty_title_field ON (pretty_title_field.id = pretty_title_semantic.field_id)
        LEFT JOIN tracker_changeset_value AS pretty_title_tcv
            ON (pretty_title_tcv.changeset_id = changeset.id AND pretty_title_tcv.field_id = pretty_title_field.id)
        LEFT JOIN tracker_changeset_value_text AS pretty_title ON (pretty_title.changeset_value_id = pretty_title_tcv.id)
        EOSQL;

        return new ParametrizedSelectFrom($select, $from, []);
    }
}
