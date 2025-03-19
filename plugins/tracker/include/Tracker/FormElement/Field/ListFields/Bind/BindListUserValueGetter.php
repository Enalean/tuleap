<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use ProjectUGroup;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use Tuleap\DB\DatabaseUUIDV7Factory;
use UserHelper;

class BindListUserValueGetter
{
    private const WEB_PAYLOAD_UGROUP_PREFIX   = 'ugroup_';
    private const UGROUP_FOR_REGISTERED_USERS = self::WEB_PAYLOAD_UGROUP_PREFIX . ProjectUGroup::REGISTERED;

    public function __construct(
        private BindDefaultValueDao $bind_defaultvalue_dao,
        private UserHelper $user_helper,
        private readonly PlatformUsersGetter $platform_users_getter,
        private readonly DatabaseUUIDV7Factory $uuid_factory,
    ) {
    }

    /**
     * @return array<int, Tracker_FormElement_Field_List_Bind_UsersValue>
     */
    public function getSubsetOfUsersValueWithUserIds(
        array $ugroups,
        array $user_ids,
        \Tracker_FormElement_Field $field,
    ): array {
        if ($this->isRegisteredUsersPartOfRequestedUserGroups($ugroups)) {
            return $this->platform_users_getter->getRegisteredUsers($this->user_helper);
        }
        return $this->getUsersValueWithUserIdsAccordingToUserStatus(
            $ugroups,
            $user_ids,
            $field,
            false
        );
    }

    /**
     * @return array<int, Tracker_FormElement_Field_List_Bind_UsersValue>
     */
    public function getActiveUsersValue(
        array $ugroups,
        \Tracker_FormElement_Field $field,
    ): array {
        if ($this->isRegisteredUsersPartOfRequestedUserGroups($ugroups)) {
            return $this->platform_users_getter->getRegisteredUsers($this->user_helper);
        }
        return $this->getUsersValueWithUserIdsAccordingToUserStatus(
            $ugroups,
            [],
            $field,
            true
        );
    }

    private function isRegisteredUsersPartOfRequestedUserGroups(array $ugroups): bool
    {
        return in_array(self::UGROUP_FOR_REGISTERED_USERS, $ugroups, true);
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_UsersValue[]
     */
    private function getUsersValueWithUserIdsAccordingToUserStatus(
        array $ugroups,
        array $bindvalue_ids,
        \Tracker_FormElement_Field $field,
        bool $filter_on_active_user,
    ): array {
        $da = $this->bind_defaultvalue_dao->getDa();

        $tracker = $field->getTracker();
        if (! $tracker) {
            return [];
        }

        $sql         = [];
        $tracker_id  = $da->escapeInt($tracker->getId());
        $user_id_sql = $bindvalue_ids ? 'WHERE user.user_id IN (' . $da->escapeIntImplode($bindvalue_ids) . ')' : '';

        foreach ($ugroups as $ugroup) {
            if (! $ugroup) {
                continue;
            }

            switch ($ugroup) {
                case 'group_members':
                    $sql[] = $this->getUGroupUtilsDynamicMembers(
                        ProjectUGroup::PROJECT_MEMBERS,
                        $bindvalue_ids,
                        $tracker
                    );
                    break;
                case 'group_admins':
                    $sql[] = $this->getUGroupUtilsDynamicMembers(
                        ProjectUGroup::PROJECT_ADMIN,
                        $bindvalue_ids,
                        $tracker
                    );
                    break;
                case 'artifact_submitters':
                    $display_name_sql = $this->user_helper->getDisplayNameSQLQuery();
                    $order_by_sql     = $this->user_helper->getDisplayNameSQLOrder();

                    $sql[] = "(
                        SELECT user.user_id, $display_name_sql, user.realname, user.user_name, user.email, user.status
                        FROM user
                        JOIN (
                            SELECT DISTINCT tracker_artifact.submitted_by FROM tracker_artifact WHERE tracker_id = $tracker_id
                        ) AS tracker_submitted_by ON (user.user_id = tracker_submitted_by.submitted_by)
                        $user_id_sql
                        ORDER BY $order_by_sql
                    )";
                    break;
                case 'artifact_modifiers':
                    $display_name_sql = $this->user_helper->getDisplayNameSQLQuery();
                    $order_by_sql     = $this->user_helper->getDisplayNameSQLOrder();

                    $sql[] = "(
                        SELECT user.user_id, $display_name_sql, user.realname, user.user_name, user.email, user.status
                        FROM user
                        JOIN (
                            SELECT DISTINCT tracker_changeset.submitted_by
                            FROM tracker_changeset
                            JOIN tracker_artifact ON (tracker_artifact.id = tracker_changeset.artifact_id)
                            WHERE tracker_id = $tracker_id
                        ) AS tracker_modified_by ON (user.user_id = tracker_modified_by.submitted_by)
                        $user_id_sql
                        ORDER BY $order_by_sql
                    )";
                    break;
                default:
                    if (preg_match('/' . preg_quote(self::WEB_PAYLOAD_UGROUP_PREFIX) . '([0-9]+)/', $ugroup, $matches)) {
                        if (strlen($matches[1]) > 2) {
                            if ($filter_on_active_user) {
                                $sql[] = $this->getActiveMembersOfStaticGroup($matches);
                            } else {
                                $sql[] = $this->getAllMembersOfStaticGroup($bindvalue_ids, $matches);
                            }
                        } else {
                            $sql[] = $this->getUGroupUtilsDynamicMembers(
                                $matches[1],
                                $bindvalue_ids,
                                $tracker
                            );
                        }
                    }
            }
        }

        $order_by_sql = $this->user_helper->getDisplayNameSQLOrder();
        $sql          = array_filter($sql);

        if (empty($sql)) {
            return [];
        }
        $query = $this->getUsersSorted($sql, $order_by_sql);
        $rows  = $this->bind_defaultvalue_dao->retrieve($query);

        if (! $rows) {
            return [];
        }

        $values = [];
        foreach ($rows as $row) {
            $values[$row['user_id']] = new Tracker_FormElement_Field_List_Bind_UsersValue(
                $this->uuid_factory->buildUUIDFromBytesData($this->uuid_factory->buildUUIDBytes()),
                $row['user_id'],
                $row['user_name'],
                $row['full_name']
            );
        }

        return $values;
    }

    private function getUsersSorted(array $sql, string $order_by_sql): string
    {
        $tempory_request = implode(' UNION ', $sql);

        return "SELECT user.user_id, user.full_name, user.user_name, user.realname FROM ($tempory_request) AS user ORDER BY $order_by_sql";
    }

    /**
     * protected for testing purpose
     */
    protected function getUGroupUtilsDynamicMembers(
        $ugroup_name,
        array $bindvalue_ids,
        \Tracker $tracker,
    ): ?string {
        return ugroup_db_get_dynamic_members(
            $ugroup_name,
            null,
            $tracker->getGroupId(),
            true,
            false,
            false,
            $bindvalue_ids
        );
    }

    private function getActiveMembersOfStaticGroup(array $matches): string
    {
        $sql_display_name  = $this->user_helper->getDisplayNameSQLQuery();
        $sql_order_by_name = $this->user_helper->getDisplayNameSQLOrder();

        return "(SELECT user.user_id, $sql_display_name, user.realname, user.user_name, user.email, user.status
                 FROM ugroup_user, user
                 WHERE user.user_id = ugroup_user.user_id
                 AND ugroup_user.ugroup_id = $matches[1]
                 AND (status='A' OR status='R')
                 ORDER BY $sql_order_by_name)";
    }

    /**
     * protected for testing purpose
     */
    protected function getAllMembersOfStaticGroup(array $bindvalue_ids, array $matches): string
    {
        return ugroup_db_get_members(
            $matches[1],
            true,
            null,
            $bindvalue_ids
        );
    }
}
