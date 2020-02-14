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

declare(strict_types = 1);

namespace Tuleap\Tracker\FormElement\Field\ListFields\Bind;

use ProjectUGroup;
use Tracker_FormElement_Field_List_Bind_DefaultvalueDao;
use Tracker_FormElement_Field_List_Bind_UsersValue;
use UserHelper;

class BindListUserValueGetter
{
    /**
     * @var Tracker_FormElement_Field_List_Bind_DefaultvalueDao
     */
    private $bind_defaultvalue_dao;
    /**
     * @var UserHelper
     */
    private $user_helper;

    public function __construct(
        Tracker_FormElement_Field_List_Bind_DefaultvalueDao $bind_defaultvalue_dao,
        UserHelper $user_helper
    ) {
        $this->bind_defaultvalue_dao = $bind_defaultvalue_dao;
        $this->user_helper           = $user_helper;
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_UsersValue[]
     */
    public function getUsersValueByKeywordAndIds(
        array $ugroups,
        $keyword,
        array $bindvalue_ids,
        \Tracker_FormElement_Field $field
    ): array {
        return $this->getUsersValueByKeywordAndIdsAccordingUserStatus(
            $ugroups,
            $keyword,
            $bindvalue_ids,
            $field,
            false
        );
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_UsersValue[]
     */
    public function getActiveUsersValue(
        array $ugroups,
        \Tracker_FormElement_Field $field
    ): array {
        return $this->getUsersValueByKeywordAndIdsAccordingUserStatus(
            $ugroups,
            null,
            [],
            $field,
            true
        );
    }

    /**
     * @return Tracker_FormElement_Field_List_Bind_UsersValue[]
     */
    private function getUsersValueByKeywordAndIdsAccordingUserStatus(
        array $ugroups,
        $keyword,
        array $bindvalue_ids,
        \Tracker_FormElement_Field $field,
        bool $filter_on_active_user
    ): array {
        $da = $this->bind_defaultvalue_dao->getDa();

        $tracker = $field->getTracker();
        if (! $tracker) {
            return [];
        }

        $sql = [];
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
                        $keyword,
                        $bindvalue_ids,
                        $tracker,
                        false,
                        false
                    );
                    break;
                case 'group_admins':
                    $sql[] = $this->getUGroupUtilsDynamicMembers(
                        ProjectUGroup::PROJECT_ADMIN,
                        $keyword,
                        $bindvalue_ids,
                        $tracker,
                        false,
                        false
                    );
                    break;
                case 'artifact_submitters':
                    $display_name_sql = $this->user_helper->getDisplayNameSQLQuery();
                    $order_by_sql     = $this->user_helper->getDisplayNameSQLOrder();
                    if ($keyword) {
                        $keyword = $da->quoteLikeValueSurround($keyword);
                    }
                    $keyword_sql = ($keyword ? "HAVING full_name LIKE $keyword" : "");

                    $sql[] = "(
                        SELECT DISTINCT user.user_id, $display_name_sql, user.realname, user.user_name, user.email, user.status
                            FROM tracker_artifact AS a
                            INNER JOIN user
                                ON ( user.user_id = a.submitted_by AND a.tracker_id = $tracker_id )
                            $user_id_sql
                            $keyword_sql
                            ORDER BY $order_by_sql
                    )";
                    break;
                case 'artifact_modifiers':
                    $display_name_sql = $this->user_helper->getDisplayNameSQLQuery();
                    $order_by_sql     = $this->user_helper->getDisplayNameSQLOrder();
                    if ($keyword) {
                        $keyword = $da->quoteLikeValueSurround($keyword);
                    }
                    $keyword_sql = ($keyword ? "HAVING full_name LIKE $keyword" : "");

                    $sql[] = "(
                        SELECT DISTINCT user.user_id, $display_name_sql, user.realname, user.user_name, user.email, user.status
                            FROM tracker_artifact AS a
                            INNER JOIN tracker_changeset c ON a.id = c.artifact_id
                            INNER JOIN user
                                ON ( user.user_id = c.submitted_by AND a.tracker_id = $tracker_id )
                            $user_id_sql
                            $keyword_sql
                            ORDER BY $order_by_sql
                    )";
                    break;
                default:
                    if (preg_match('/ugroup_([0-9]+)/', $ugroup, $matches)) {
                        if (strlen($matches[1]) > 2) {
                            if ($filter_on_active_user) {
                                $sql[] = $this->getActiveMembersOfStaticGroup($matches);
                            } else {
                                $sql[] = $this->getAllMembersOfStaticGroup($keyword, $bindvalue_ids, $matches);
                            }
                        } else {
                            $show_suspended = false;
                            $sql[]          = $this->getUGroupUtilsDynamicMembers(
                                $matches[1],
                                $keyword,
                                $bindvalue_ids,
                                $tracker,
                                $show_suspended,
                                false
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

        if (!$rows) {
            return [];
        }

        $values = [];
        foreach ($rows as $row) {
            $values[$row['user_id']] = new Tracker_FormElement_Field_List_Bind_UsersValue(
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
        $keyword,
        array $bindvalue_ids,
        \Tracker $tracker,
        bool $show_suspended,
        bool $show_deleted
    ): ?string {
        return ugroup_db_get_dynamic_members(
            $ugroup_name,
            $tracker->getId(),
            $tracker->getGroupId(),
            true,
            $keyword,
            $show_suspended,
            $show_deleted,
            $bindvalue_ids
        );
    }

    /**
     * protected for testing purpose
     */
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
    protected function getAllMembersOfStaticGroup($keyword, array $bindvalue_ids, array $matches): string
    {
        return ugroup_db_get_members(
            $matches[1],
            true,
            $keyword,
            $bindvalue_ids
        );
    }
}
