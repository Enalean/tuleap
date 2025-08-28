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

final readonly class ReverseLinkTypeSelectFromBuilder implements BuildLinkTypeSelectFrom
{
    #[\Override]
    public function getSelectFrom(Option $artifact_id, array $artifact_ids): IProvideParametrizedSelectAndFromAndWhereSQLFragments
    {
        $select =  <<<EOSQL
         IFNULL(reverse_artlink.nature, '') AS '@link_type',
         IFNULL(nature.reverse_label, '') AS 'reverse',
         artifact.id AS reverse_art_id
        EOSQL;
        $from   = <<<EOSQL
        LEFT JOIN tracker_field                        AS reverse_f          ON (reverse_f.tracker_id = artifact.tracker_id AND reverse_f.formElement_type = 'art_link' AND reverse_f.use_it = 1)
        LEFT JOIN tracker_changeset_value              AS reverse_cv         ON (reverse_cv.changeset_id = artifact.last_changeset_id AND reverse_cv.field_id = reverse_f.id)
        LEFT JOIN tracker_changeset_value_artifactlink AS reverse_artlink    ON (reverse_artlink.changeset_value_id = reverse_cv.id AND reverse_artlink.artifact_id = ?)
        LEFT JOIN plugin_tracker_artifactlink_natures AS nature ON (reverse_artlink.nature = nature.shortname)
        EOSQL;

        return $artifact_id->match(
            function (int $id) use ($select, $from): IProvideParametrizedSelectAndFromAndWhereSQLFragments {
                return new ParametrizedSelectFromAndWhere(
                    $select,
                    $from,
                    [$id],
                    Option::nothing(string()),
                    []
                );
            },
            function (): IProvideParametrizedSelectAndFromAndWhereSQLFragments {
                return new ParametrizedSelectFromAndWhere(
                    '',
                    '',
                    [],
                    Option::nothing(string()),
                    []
                );
            }
        );
    }
}
