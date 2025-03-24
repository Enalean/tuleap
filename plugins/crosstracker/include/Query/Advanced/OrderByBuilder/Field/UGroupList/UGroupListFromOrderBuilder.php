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

namespace Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\Field\UGroupList;

use ParagonIE\EasyDB\EasyStatement;
use ProjectUGroup;
use Tuleap\CrossTracker\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderByDirection;
use UGroupManager;

final readonly class UGroupListFromOrderBuilder
{
    public function __construct(
        private UGroupManager $user_group_manager,
    ) {
    }

    /**
     * @param list<int> $field_ids
     */
    public function getFromOrder(array $field_ids, OrderByDirection $direction): ParametrizedFromOrder
    {
        $suffix                                      = md5($direction->value);
        $tracker_field_alias                         = "TF_$suffix";
        $changeset_value_alias                       = "CV_$suffix";
        $tracker_changeset_value_list_alias          = "TCVL_$suffix";
        $tracker_field_list_bind_ugroups_value_alias = "TFLBYV_$suffix";
        $ugroup_alias                                = "UG_$suffix";

        $fields_id_statement = EasyStatement::open()->in("$tracker_field_alias.id IN(?*)", $field_ids);
        $user_group_cases    = $this->getTranslatedUserGroupNamesCases();
        $from                = <<<EOSQL
         LEFT JOIN tracker_field AS $tracker_field_alias
            ON (tracker.id = $tracker_field_alias.tracker_id AND $fields_id_statement)
        LEFT JOIN tracker_changeset_value AS $changeset_value_alias
            ON ($tracker_field_alias.id = $changeset_value_alias.field_id AND changeset.id = $changeset_value_alias.changeset_id)
        LEFT JOIN tracker_changeset_value_list AS $tracker_changeset_value_list_alias
            ON $tracker_changeset_value_list_alias.changeset_value_id = $changeset_value_alias.id
        LEFT JOIN tracker_field_list_bind_ugroups_value as $tracker_field_list_bind_ugroups_value_alias
            ON $tracker_field_list_bind_ugroups_value_alias.id = $tracker_changeset_value_list_alias.bindvalue_id
        LEFT JOIN (
            SELECT CASE $ugroup_alias.ugroup_id
                       {$user_group_cases['sql']}
                       ELSE $ugroup_alias.name
                   END AS order_name,
                   $ugroup_alias.ugroup_id
            FROM ugroup AS $ugroup_alias
        ) AS $ugroup_alias ON $tracker_field_list_bind_ugroups_value_alias.ugroup_id = $ugroup_alias.ugroup_id
        EOSQL;

        return new ParametrizedFromOrder($from, [...$field_ids, ...$user_group_cases['parameters']], "$ugroup_alias.order_name $direction->value");
    }

    /**
     * @return array{
     *     sql: string,
     *     parameters: array,
     * }
     */
    private function getTranslatedUserGroupNamesCases(): array
    {
        $result     = '';
        $parameters = [];
        foreach (ProjectUGroup::NORMALIZED_NAMES as $user_group_name) {
            $id = $this->user_group_manager->getDynamicUGoupIdByName($user_group_name);
            if ($id === false) {
                continue;
            }
            $user_group      = $this->user_group_manager->getById($id);
            $translated_name = $user_group->getTranslatedName();
            if ($translated_name === null) {
                continue;
            }
            $result      .= "WHEN ? THEN ?\n";
            $parameters[] = $id;
            $parameters[] = $translated_name;
        }

        return [
            'sql'        => $result,
            'parameters' => $parameters,
        ];
    }
}
