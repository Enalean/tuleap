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

use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;
use Tuleap\Option\Option;

final readonly class ReverseLinkTypeSelectFromBuilder implements BuildLinkTypeSelectFrom
{
    #[\Override]
    public function getSelectFrom(Option $target_artifact_id_for_reverse_links): IProvideParametrizedSelectAndFromSQLFragments
    {
        $select =  <<<EOSQL
         IFNULL(reverse_artlink.nature, '') AS '@link_type',
         artifact.id AS reverse_art_id
        EOSQL;
        $from   = <<<EOSQL
        LEFT JOIN tracker_field                        AS reverse_f          ON (reverse_f.tracker_id = artifact.tracker_id AND reverse_f.formElement_type = 'art_link' AND reverse_f.use_it = 1)
        LEFT JOIN tracker_changeset_value              AS reverse_cv         ON (reverse_cv.changeset_id = artifact.last_changeset_id AND reverse_cv.field_id = reverse_f.id)
        LEFT JOIN tracker_changeset_value_artifactlink AS reverse_artlink    ON (reverse_artlink.changeset_value_id = reverse_cv.id AND reverse_artlink.artifact_id = ?)
        EOSQL;

        return $target_artifact_id_for_reverse_links->match(
            function (int $id) use ($select, $from): IProvideParametrizedSelectAndFromSQLFragments {
                return new ParametrizedSelectFrom($select, $from, [$id]);
            },
            function (): IProvideParametrizedSelectAndFromSQLFragments {
                return new ParametrizedSelectFrom('', '', []);
            }
        );
    }
}
