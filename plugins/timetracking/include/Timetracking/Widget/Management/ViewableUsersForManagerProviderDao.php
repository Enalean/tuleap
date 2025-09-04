<?php
/**
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

namespace Tuleap\Timetracking\Widget\Management;

use PFUser;
use Tuleap\Option\Option;
use Tuleap\User\ProvideUserFromRow;

final class ViewableUsersForManagerProviderDao extends ManagerPermissionsDao implements ProvideViewableUsersForManager
{
    public function __construct(private ProvideUserFromRow $provide_user_from_row)
    {
        parent::__construct();
    }

    #[\Override]
    public function getPaginatedViewableUsersForManager(
        PFUser $manager,
        string $query,
        int $offset,
        int $limit,
    ): \PaginatedUserCollection {
        $username_stmt = \ParagonIE\EasyDB\EasyStatement::open();
        $realname_stmt = \ParagonIE\EasyDB\EasyStatement::open();
        foreach (explode(' ', $query) as $word) {
            $username_stmt->orWith('user_name LIKE ?', '%' . $this->getDB()->escapeLikeValue($word) . '%');
            $realname_stmt->orWith('realname LIKE ?', '%' . $this->getDB()->escapeLikeValue($word) . '%');
        }

        $manager_is_timetracking_reader = $this->getWritersSqlQueryWhenManagerIsTimetrackingAdmin(
            $manager,
            Option::nothing(PFUser::class),
        );

        $manager_is_timetracking_reader_of_a_project_member_writer = $this->getWritersSqlQueryWhenManagerIsTimetrackingReaderOfProjectMemberWriter(
            $manager,
            Option::nothing(PFUser::class),
        );

        $manager_is_project_member_and_timetracking_reader = $this->getWritersSqlQueryWhenManagerIsProjectMemberAndTimetrackingReader(
            $manager,
            Option::nothing(PFUser::class),
        );

        $manager_is_tracker_admin = $this->getWritersSqlQueryWhenManagerIsTrackerAdmin(
            $manager,
            Option::nothing(PFUser::class),
        );

        $manager_is_project_member_and_tracker_admin = $this->getWritersSqlQueryWhenManagerIsProjectMemberAndTrackerAdmin(
            $manager,
            Option::nothing(PFUser::class),
        );

        $manager_is_project_admin = $this->getWritersSqlQueryWhenManagerIsProjectAdmin(
            $manager,
            Option::nothing(PFUser::class),
        );

        $manager_is_project_admin_of_a_project_member_writer = $this->getWritersSqlQueryWhenManagerIsProjectAdminOfAProjectMemberWriter(
            $manager,
            Option::nothing(PFUser::class),
        );

        $union = new SqlUnion(
            $manager_is_timetracking_reader,
            $manager_is_timetracking_reader_of_a_project_member_writer,
            $manager_is_project_member_and_timetracking_reader,
            $manager_is_tracker_admin,
            $manager_is_project_member_and_tracker_admin,
            $manager_is_project_admin,
            $manager_is_project_admin_of_a_project_member_writer,
        );

        $sql = <<<EOS
            SELECT user.*
            FROM user
                INNER JOIN (
                    {$union->sql}
                ) AS writers
                ON (
                    writers.user_id = user.user_id
                    AND status IN ('A', 'R')
                    AND (($username_stmt) OR ($realname_stmt))
                )
            ORDER BY user_name
            LIMIT ?, ?
            EOS;

        $rows = $this->getDB()->run(
            $sql,
            ...$union->parameters,
            ...array_values($username_stmt->values()),
            ...array_values($realname_stmt->values()),
            ...[$offset, $limit],
        );

        $total_count = $this->getDB()->cell(
            <<<EOS
            SELECT count(*)
            FROM user
                INNER JOIN (
                    {$union->sql}
                ) AS writers
                ON (
                    writers.user_id = user.user_id
                    AND status IN ('A', 'R')
                    AND (($username_stmt) OR ($realname_stmt))
                )
            ORDER BY user_name
            EOS,
            ...$union->parameters,
            ...array_values($username_stmt->values()),
            ...array_values($realname_stmt->values()),
        );

        return new \PaginatedUserCollection(
            array_map(
                fn (array $row) => $this->provide_user_from_row->getUserInstanceFromRow($row),
                $rows,
            ),
            $total_count,
        );
    }
}
