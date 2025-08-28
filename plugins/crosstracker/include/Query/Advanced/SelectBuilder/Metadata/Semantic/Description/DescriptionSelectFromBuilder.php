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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Description;

use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFromAndWhere;
use Tuleap\Option\Option;
use function Psl\Type\string;

final class DescriptionSelectFromBuilder
{
    public function getSelectFrom(): IProvideParametrizedSelectAndFromAndWhereSQLFragments
    {
        $select = "description.value AS '@description', description.body_format AS '@description_format'";
        $from   = <<<EOSQL
        LEFT JOIN tracker_semantic_description AS description_semantic ON (description_semantic.tracker_id = artifact.tracker_id)
        LEFT JOIN tracker_field AS description_field ON (description_field.id = description_semantic.field_id)
        LEFT JOIN tracker_changeset_value AS description_tcv
            ON (description_tcv.changeset_id = changeset.id AND description_tcv.field_id = description_field.id)
        LEFT JOIN tracker_changeset_value_text AS description ON (description.changeset_value_id = description_tcv.id)
        EOSQL;

        return new ParametrizedSelectFromAndWhere(
            $select,
            $from,
            [],
            Option::nothing(string()),
            []
        );
    }
}
