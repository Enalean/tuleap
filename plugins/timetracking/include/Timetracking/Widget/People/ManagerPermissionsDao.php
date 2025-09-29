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

namespace Tuleap\Timetracking\Widget\People;

use ParagonIE\EasyDB\EasyStatement;
use PFUser;
use ProjectUGroup;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;
use Tuleap\Tracker\Tracker;

abstract class ManagerPermissionsDao extends DataAccessObject
{
    /**
     * @param Option<PFUser> $writer_to_search
     */
    protected function getWritersSqlQueryWhenManagerIsTimetrackingAdmin(
        PFUser $manager,
        Option $writer_to_search,
    ): SqlQuery {
        $writer_to_search_stmt = EasyStatement::open();
        $writer_to_search->apply(fn(PFUser $user) => $writer_to_search_stmt->andWith('writers.user_id = ?', $user->getId()));

        return new SqlQuery(
            <<<EOS
            SELECT writers.user_id
            FROM plugin_timetracking_writers
                INNER JOIN plugin_timetracking_enabled_trackers
                    ON (plugin_timetracking_writers.tracker_id = plugin_timetracking_enabled_trackers.tracker_id)
                INNER JOIN plugin_timetracking_readers
                    ON (
                        plugin_timetracking_writers.tracker_id = plugin_timetracking_readers.tracker_id
                    )
                INNER JOIN ugroup_user AS writers
                    ON (writers.ugroup_id = plugin_timetracking_writers.ugroup_id AND $writer_to_search_stmt)
                INNER JOIN ugroup_user AS readers
                    ON (readers.ugroup_id = plugin_timetracking_readers.ugroup_id AND readers.user_id = ?)
            EOS,
            ...$writer_to_search_stmt->values(),
            ...[$manager->getId()],
        );
    }

    /**
     * @param Option<PFUser> $writer_to_search
     */
    protected function getWritersSqlQueryWhenManagerIsTimetrackingReaderOfProjectMemberWriter(
        PFUser $manager,
        Option $writer_to_search,
    ): SqlQuery {
        $writer_to_search_stmt = EasyStatement::open();
        $writer_to_search->apply(fn(PFUser $user) => $writer_to_search_stmt->andWith('writers.user_id = ?', $user->getId()));

        return new SqlQuery(
            <<<EOS
            SELECT writers.user_id
            FROM plugin_timetracking_writers
                INNER JOIN plugin_timetracking_enabled_trackers
                    ON (plugin_timetracking_writers.tracker_id = plugin_timetracking_enabled_trackers.tracker_id)
                INNER JOIN plugin_timetracking_readers
                    ON (
                        plugin_timetracking_writers.tracker_id = plugin_timetracking_readers.tracker_id
                    )
                INNER JOIN tracker ON (tracker.id = plugin_timetracking_writers.tracker_id)
                INNER JOIN user_group AS writers
                    ON (
                        plugin_timetracking_writers.ugroup_id = ?
                        AND writers.group_id = tracker.group_id
                        AND $writer_to_search_stmt
                    )
                INNER JOIN ugroup_user AS readers
                    ON (readers.ugroup_id = plugin_timetracking_readers.ugroup_id AND readers.user_id = ?)
            EOS,
            ProjectUGroup::PROJECT_MEMBERS,
            ...$writer_to_search_stmt->values(),
            ...[$manager->getId()],
        );
    }

    /**
     * @param Option<PFUser> $writer_to_search
     */
    protected function getWritersSqlQueryWhenManagerIsProjectMemberAndTimetrackingReader(
        PFUser $manager,
        Option $writer_to_search,
    ): SqlQuery {
        $writer_to_search_stmt = EasyStatement::open();
        $writer_to_search->apply(fn(PFUser $user) => $writer_to_search_stmt->andWith('writers.user_id = ?', $user->getId()));

        return new SqlQuery(
            <<<EOS
            SELECT writers.user_id
            FROM plugin_timetracking_writers
                INNER JOIN plugin_timetracking_enabled_trackers
                    ON (plugin_timetracking_writers.tracker_id = plugin_timetracking_enabled_trackers.tracker_id)
                INNER JOIN plugin_timetracking_readers
                    ON (
                        plugin_timetracking_writers.tracker_id = plugin_timetracking_readers.tracker_id
                    )
                INNER JOIN tracker ON (tracker.id = plugin_timetracking_writers.tracker_id)
                INNER JOIN user_group AS readers
                    ON (
                        plugin_timetracking_readers.ugroup_id = ?
                        AND readers.group_id = tracker.group_id
                        AND readers.user_id = ?
                    )
                INNER JOIN ugroup_user AS writers
                    ON (writers.ugroup_id = plugin_timetracking_writers.ugroup_id AND $writer_to_search_stmt)
            EOS,
            ProjectUGroup::PROJECT_MEMBERS,
            $manager->getId(),
            ...$writer_to_search_stmt->values(),
        );
    }

    /**
     * @param Option<PFUser> $writer_to_search
     */
    protected function getWritersSqlQueryWhenManagerIsTrackerAdmin(
        PFUser $manager,
        Option $writer_to_search,
    ): SqlQuery {
        $writer_to_search_stmt = EasyStatement::open();
        $writer_to_search->apply(fn(PFUser $user) => $writer_to_search_stmt->andWith('writers.user_id = ?', $user->getId()));

        return new SqlQuery(
            <<<EOS
            SELECT writers.user_id
            FROM plugin_timetracking_writers
                INNER JOIN plugin_timetracking_enabled_trackers
                    ON (plugin_timetracking_writers.tracker_id = plugin_timetracking_enabled_trackers.tracker_id)
                INNER JOIN ugroup_user AS writers
                    ON (writers.ugroup_id = plugin_timetracking_writers.ugroup_id AND $writer_to_search_stmt)
                INNER JOIN permissions
                    ON (
                        permissions.object_id = CAST(plugin_timetracking_writers.tracker_id AS CHAR CHARACTER SET utf8)
                        AND permissions.permission_type = ?
                    )
                INNER JOIN ugroup_user AS tracker_admins
                    ON (tracker_admins.ugroup_id = permissions.ugroup_id AND tracker_admins.user_id = ?)
            EOS,
            ...$writer_to_search_stmt->values(),
            ...[Tracker::PERMISSION_ADMIN, $manager->getId()],
        );
    }

    /**
     * @param Option<PFUser> $writer_to_search
     */
    protected function getWritersSqlQueryWhenManagerIsProjectMemberAndTrackerAdmin(
        PFUser $manager,
        Option $writer_to_search,
    ): SqlQuery {
        $writer_to_search_stmt = EasyStatement::open();
        $writer_to_search->apply(fn(PFUser $user) => $writer_to_search_stmt->andWith('writers.user_id = ?', $user->getId()));

        return new SqlQuery(
            <<<EOS
            SELECT writers.user_id
            FROM plugin_timetracking_writers
                INNER JOIN plugin_timetracking_enabled_trackers
                    ON (plugin_timetracking_writers.tracker_id = plugin_timetracking_enabled_trackers.tracker_id)
                INNER JOIN ugroup_user AS writers
                    ON (writers.ugroup_id = plugin_timetracking_writers.ugroup_id AND $writer_to_search_stmt)
                INNER JOIN permissions
                    ON (
                        permissions.object_id = CAST(plugin_timetracking_writers.tracker_id AS CHAR CHARACTER SET utf8)
                        AND permissions.permission_type = ?
                        AND permissions.ugroup_id = ?
                    )
                INNER JOIN tracker ON (tracker.id = plugin_timetracking_writers.tracker_id)
                INNER JOIN user_group AS project_members
                    ON (
                        project_members.group_id = tracker.group_id
                        AND project_members.user_id = ?
                    )
            EOS,
            ...$writer_to_search_stmt->values(),
            ...[Tracker::PERMISSION_ADMIN, ProjectUGroup::PROJECT_MEMBERS, $manager->getId()],
        );
    }

    /**
     * @param Option<PFUser> $writer_to_search
     */
    protected function getWritersSqlQueryWhenManagerIsProjectAdmin(
        PFUser $manager,
        Option $writer_to_search,
    ): SqlQuery {
        $writer_to_search_stmt = EasyStatement::open();
        $writer_to_search->apply(fn(PFUser $user) => $writer_to_search_stmt->andWith('writers.user_id = ?', $user->getId()));

        return new SqlQuery(
            <<<EOS
                SELECT writers.user_id
                FROM plugin_timetracking_writers
                    INNER JOIN plugin_timetracking_enabled_trackers
                        ON (plugin_timetracking_writers.tracker_id = plugin_timetracking_enabled_trackers.tracker_id)
                    INNER JOIN ugroup_user AS writers
                        ON (writers.ugroup_id = plugin_timetracking_writers.ugroup_id AND $writer_to_search_stmt)
                    INNER JOIN tracker ON (tracker.id = plugin_timetracking_writers.tracker_id)
                    INNER JOIN user_group
                        ON (
                            user_group.group_id = tracker.group_id
                            AND user_group.user_id = ?
                            AND user_group.admin_flags = 'A'
                        )
                EOS,
            ...$writer_to_search_stmt->values(),
            ...[$manager->getId()],
        );
    }

    /**
     * @param Option<PFUser> $writer_to_search
     */
    protected function getWritersSqlQueryWhenManagerIsProjectAdminOfAProjectMemberWriter(
        PFUser $manager,
        Option $writer_to_search,
    ): SqlQuery {
        $writer_to_search_stmt = EasyStatement::open();
        $writer_to_search->apply(fn(PFUser $user) => $writer_to_search_stmt->andWith('writers.user_id = ?', $user->getId()));

        return new SqlQuery(
            <<<EOS
            SELECT writers.user_id
            FROM plugin_timetracking_writers
                INNER JOIN plugin_timetracking_enabled_trackers
                    ON (plugin_timetracking_writers.tracker_id = plugin_timetracking_enabled_trackers.tracker_id)
                INNER JOIN tracker ON (tracker.id = plugin_timetracking_writers.tracker_id)
                INNER JOIN user_group AS writers
                    ON (
                        plugin_timetracking_writers.ugroup_id = ?
                        AND writers.group_id = tracker.group_id
                        AND $writer_to_search_stmt
                    )
                INNER JOIN user_group AS readers
                    ON (
                        readers.group_id = tracker.group_id
                        AND readers.user_id = ?
                        AND readers.admin_flags = 'A'
                    )
            EOS,
            ProjectUGroup::PROJECT_MEMBERS,
            ...$writer_to_search_stmt->values(),
            ...[$manager->getId()],
        );
    }
}
