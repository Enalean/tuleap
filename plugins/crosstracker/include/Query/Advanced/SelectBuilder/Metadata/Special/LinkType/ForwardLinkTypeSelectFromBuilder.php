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

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFromAndWhere;
use Tuleap\Option\Option;
use function Psl\Type\string;

final readonly class ForwardLinkTypeSelectFromBuilder implements BuildLinkTypeSelectFrom
{
    #[\Override]
    public function getSelectFrom(Option $artifact_id, array $artifact_ids): IProvideParametrizedSelectAndFromAndWhereSQLFragments
    {
        $select =  <<<EOSQL
        IFNULL(forward_artlink.nature, '') AS '@link_type',
        IFNULL(nature.forward_label, '') AS 'forward',
        forward_artlink.artifact_id AS forward_art_id
        EOSQL;
        $from   = <<<EOSQL

        LEFT JOIN tracker_artifact AS source ON (source.id = ?)
        LEFT JOIN tracker_changeset AS source_changeset ON (source_changeset.id = source.last_changeset_id)
        LEFT JOIN tracker_changeset_value AS cv ON (cv.changeset_id = source_changeset.id)
        LEFT JOIN tracker_changeset_value_artifactlink AS forward_artlink ON (
            forward_artlink.changeset_value_id = cv.id AND
            forward_artlink.artifact_id = artifact.id
        )
        LEFT JOIN plugin_tracker_artifactlink_natures AS nature ON (forward_artlink.nature = nature.shortname)
        EOSQL;

        return $artifact_id->match(
            function (int $id) use ($select, $from, $artifact_ids): IProvideParametrizedSelectAndFromAndWhereSQLFragments {
                $ids_statement = EasyStatement::open()->in('artifact.id IN (?*)', $artifact_ids);

                return new ParametrizedSelectFromAndWhere(
                    $select,
                    $from,
                    [$id],
                    Option::fromValue($ids_statement->sql() . ' AND forward_artlink.artifact_id IS NOT NULL'),
                    $ids_statement->values(),
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
