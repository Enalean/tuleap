<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query;

use ForgeConfig;
use ParagonIE\EasyDB\EasyStatement;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tracker_ReportDao;
use Tuleap\DB\DataAccessObject;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\dao\TooManyMatchingArtifactsException;

final class QueryDao extends DataAccessObject
{
    /**
     * @param Option<IProvideParametrizedFromAndWhereSQLFragments> $additional_from_where
     */
    public function searchMatchingIds(
        int $project_id,
        int $tracker_id,
        Option $additional_from_where,
        PFUser $user,
        array $permissions,
        ?int $contributor_field_id,
    ): array {
        $instances       = ['artifact_type' => $tracker_id];
        $ugroups         = $user->getUgroups($project_id, $instances);
        $static_ugroups  = $user->getStaticUgroups($project_id);
        $dynamic_ugroups = $user->getDynamicUgroups($project_id, $instances);
        $user_is_admin   = $this->userIsAdmin($user, $project_id, $permissions, $ugroups);

        $from_where = new ParametrizedFromWhere(
            "tracker_artifact AS artifact
                 INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)",
            "artifact.tracker_id = ?",
            [],
            [$tracker_id]
        );

        if (! $user_is_admin) {
            $from_where = new ParametrizedAndFromWhere(
                $from_where,
                $this->getSqlFragmentForArtifactPermissions($ugroups)
            );
        }

        if ($this->submitterOnlyApplies($user_is_admin, $permissions, $ugroups)) {
            $from_where = new ParametrizedAndFromWhere(
                $from_where,
                new ParametrizedFromWhere('', 'artifact.submitted_by = ?', [], [$user->getId()])
            );
        }

        $from_where = $additional_from_where
            ->mapOr(
                static function ($additional_from_where) use ($from_where): IProvideParametrizedFromAndWhereSQLFragments {
                    return new ParametrizedAndFromWhere($from_where, $additional_from_where);
                },
                $from_where,
            );

        // $sqls => SELECT UNION SELECT UNION SELECT ...
        $sqls = $this->getSqlFragmentsAccordinglyToTrackerPermissions(
            $user_is_admin,
            $from_where,
            $project_id,
            $tracker_id,
            $permissions,
            $ugroups,
            $static_ugroups,
            $dynamic_ugroups,
            $contributor_field_id
        );

        if (count($sqls) === 0) {
            return [];
        } else {
            // GROUP CONCAT is meant to be used on very large dataset
            // we should move the group_concat_max_len to upper limit
            $this->getDB()->run("SET SESSION group_concat_max_len = 134217728");

            $queries    = [];
            $parameters = [];
            foreach ($sqls as $sql) {
                $queries[]  = $sql->getQuery();
                $parameters = array_merge($parameters, $sql->getParameters());
            }

            $sql  = " SELECT id, last_changeset_id";
            $sql .= " FROM (" . implode(' UNION ', $queries) . ") AS R GROUP BY id, last_changeset_id";

            $max_artifacts_in_report = ForgeConfig::getInt(Tracker_ReportDao::MAX_ARTIFACTS_IN_REPORT);
            if ($max_artifacts_in_report > 0) {
                $count_query = 'SELECT COUNT(*) as nb FROM (' . $sql . ') AS COUNT_ARTS';
                $nb          = $this->getDB()->cell($count_query, ...$parameters);
                if ($nb >= $max_artifacts_in_report) {
                    throw new TooManyMatchingArtifactsException($tracker_id, $nb, $max_artifacts_in_report);
                }
            }

            return $this->getDB()->run($sql, ...$parameters);
        }
    }

    private function userIsAdmin(PFUser $user, int $project_id, array $permissions, array $ugroups): bool
    {
        return $user->isSuperUser() ||
               $user->isMember($project_id, 'A') ||
               $this->hasPermissionFor(Tracker::PERMISSION_ADMIN, $permissions, $ugroups);
    }

    private function hasPermissionFor(string $permission_type, array $permissions, array $ugroups): bool
    {
        return isset($permissions[$permission_type]) && count(array_intersect($ugroups, $permissions[$permission_type])) > 0;
    }

    private function submitterOnlyApplies(bool $user_is_admin, array $permissions, array $ugroups): bool
    {
            return $this->hasPermissionFor(Tracker::PERMISSION_SUBMITTER_ONLY, $permissions, $ugroups) &&
                ! ($user_is_admin ||
                    $this->hasPermissionFor(Tracker::PERMISSION_FULL, $permissions, $ugroups) ||
                    $this->hasPermissionFor(Tracker::PERMISSION_SUBMITTER, $permissions, $ugroups) ||
                    $this->hasPermissionFor(Tracker::PERMISSION_ASSIGNEE, $permissions, $ugroups));
    }

    private function getSqlFragmentForArtifactPermissions(array $ugroups): ParametrizedFromWhere
    {
        $in = EasyStatement::open()->in('?*', $ugroups);

        return new ParametrizedFromWhere(
            " LEFT JOIN permissions
                  ON (permissions.object_id = CAST(c.artifact_id AS CHAR CHARACTER SET utf8)
                      AND permissions.permission_type = ?)
                ",
            " (artifact.use_artifact_permissions = 0 OR permissions.ugroup_id IN ($in)) ",
            [Artifact::PERMISSION_ACCESS],
            $in->values(),
        );
    }

    /**
     * @return ParametrizedQuery[]
     */
    private function getSqlFragmentsAccordinglyToTrackerPermissions(
        bool $user_is_admin,
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        int $project_id,
        int $tracker_id,
        array $permissions,
        array $ugroups,
        array $static_ugroups,
        array $dynamic_ugroups,
        ?int $contributor_field_id,
    ): array {
        $sqls = [];
        //Does the user member of at least one group which has ACCESS_FULL or is super user?
        if (
            $user_is_admin
            || $this->hasPermissionFor(Tracker::PERMISSION_FULL, $permissions, $ugroups)
            || $this->submitterOnlyApplies($user_is_admin, $permissions, $ugroups)
        ) {
            $sqls[] = new ParametrizedQuery("SELECT c.artifact_id AS id, c.id AS last_changeset_id ", $from_where);
        } else {
            $sqls = $this->getSqlFragmentsAccordinglyToAssigneeOrSubmitterAccessPermissions(
                $from_where,
                $project_id,
                $tracker_id,
                $permissions,
                $ugroups,
                $static_ugroups,
                $dynamic_ugroups,
                $contributor_field_id,
            );
        }
        return $sqls;
    }

    private function getSqlFilterForContributorGroup(
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        ?int $contributor_field_id,
        ParametrizedFrom $join_user_constraint,
    ): ParametrizedQuery {
        return new ParametrizedQuery(
            "SELECT c.artifact_id AS id, c.id AS last_changeset_id",
            new ParametrizedAndFromWhere(
                $from_where,
                new ParametrizedAndFromWhere(
                    new ParametrizedFromWhere(
                        "INNER JOIN tracker_changeset_value AS tcv ON (
                            tcv.field_id = ?
                            AND tcv.changeset_id = c.id)
                          INNER JOIN tracker_changeset_value_list AS tcvl ON (
                            tcvl.changeset_value_id = tcv.id)",
                        '',
                        [$contributor_field_id],
                        [],
                    ),
                    ParametrizedFromWhere::fromParametrizedFrom($join_user_constraint),
                )
            )
        );
    }

    /**
     * @return ParametrizedQuery[]
     */
    private function getSqlFragmentsAccordinglyToAssigneeOrSubmitterAccessPermissions(
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        int $project_id,
        int $tracker_id,
        array $permissions,
        array $ugroups,
        array $static_ugroups,
        array $dynamic_ugroups,
        ?int $contributor_field_id,
    ): array {
        $sqls = [];

        //Does the user member of at least one group which has ACCESS_SUBMITTER ?
        if ($this->hasPermissionFor(Tracker::PERMISSION_SUBMITTER, $permissions, $ugroups)) {
            $sqls = array_merge(
                $sqls,
                $this->getSqlFragmentForAccessToArtifactSubmittedByGroup(
                    $from_where,
                    $project_id,
                    $tracker_id,
                    $permissions[Tracker::PERMISSION_SUBMITTER],
                    $static_ugroups,
                    $dynamic_ugroups
                )
            );
        }

        //Does the user member of at least one group which has ACCESS_ASSIGNEE ?
        if ($contributor_field_id && $this->hasPermissionFor(Tracker::PERMISSION_ASSIGNEE, $permissions, $ugroups)) {
            $sqls = array_merge(
                $sqls,
                $this->getSqlFragmentForAccessToArtifactAssignedToGroup(
                    $from_where,
                    $project_id,
                    $tracker_id,
                    $permissions[Tracker::PERMISSION_ASSIGNEE],
                    $static_ugroups,
                    $dynamic_ugroups,
                    $contributor_field_id,
                )
            );
        }

        return $sqls;
    }

    /**
     * @return ParametrizedQuery[]
     */
    private function getSqlFragmentForAccessToArtifactSubmittedByGroup(
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        int $project_id,
        int $tracker_id,
        array $allowed_ugroups,
        array $static_ugroups,
        array $dynamic_ugroups,
    ): array {
        $sqls = [];

        // {{{ The static ugroups
        if ($this->hasPermissionForStaticUgroup($static_ugroups, $allowed_ugroups)) {
            $in = EasyStatement::open()->in('?*', array_intersect($static_ugroups, $allowed_ugroups));

            $sqls[] = $this->getSqlFilterForSubmittedByGroup(
                $from_where,
                new ParametrizedFrom(
                    "INNER JOIN ugroup_user uu ON (artifact.submitted_by = uu.user_id AND uu.ugroup_id IN ($in))",
                    $in->values(),
                )
            );
        }
        // }}}

        // {{{ tracker_admins
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::TRACKER_ADMIN, $dynamic_ugroups, $allowed_ugroups)) {
            $sqls[] = $this->getSqlFilterForSubmittedByGroup(
                $from_where,
                new ParametrizedFrom(
                    'INNER JOIN tracker_perm AS p ON (
                        artifact.submitted_by = p.user_id
                        AND p.tracker_id = ?
                        AND p.perm_level >= 2)',
                    [$tracker_id],
                )
            );
        }
        //}}}

        // {{{ project_members
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::PROJECT_MEMBERS, $dynamic_ugroups, $allowed_ugroups)) {
            $sqls[] = $this->getSqlFilterForSubmittedByGroup(
                $from_where,
                new ParametrizedFrom(
                    'INNER JOIN user_group AS ug ON (
                        artifact.submitted_by = ug.user_id
                        AND ug.group_id = ?)',
                    [$project_id],
                )
            );
        }
        //}}}

        // {{{ project_admins
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::PROJECT_ADMIN, $dynamic_ugroups, $allowed_ugroups)) {
            $sqls[] = $this->getSqlFilterForSubmittedByGroup(
                $from_where,
                new ParametrizedFrom(
                    'INNER JOIN user_group ug ON (
                        artifact.submitted_by = ug.user_id
                        AND ug.group_id = ?
                        AND ug.admin_flags = "A")',
                    [$project_id],
                )
            );
        }
        //}}}

        return $sqls;
    }

    /**
     * @return ParametrizedQuery[]
     */
    private function getSqlFragmentForAccessToArtifactAssignedToGroup(
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        int $project_id,
        int $tracker_id,
        array $allowed_ugroups,
        array $static_ugroups,
        array $dynamic_ugroups,
        ?int $contributor_field_id,
    ): array {
        $sqls = [];

        // {{{ The static ugroups
        if ($this->hasPermissionForStaticUgroup($static_ugroups, $allowed_ugroups)) {
            $in = EasyStatement::open()->in('?*', array_intersect($static_ugroups, $allowed_ugroups));

            $sqls[] = $this->getSqlFilterForContributorGroup(
                $from_where,
                $contributor_field_id,
                new ParametrizedFrom(
                    "INNER JOIN ugroup_user AS uu ON (
                        uu.user_id = tcvl.bindvalue_id
                        AND uu.ugroup_id IN ($in)
                    )",
                    $in->values(),
                )
            );
        }
        // }}}

        // {{{ tracker_admins
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::TRACKER_ADMIN, $dynamic_ugroups, $allowed_ugroups)) {
            $sqls[] = $this->getSqlFilterForContributorGroup(
                $from_where,
                $contributor_field_id,
                new ParametrizedFrom(
                    'INNER JOIN tracker_perm AS p ON (
                        p.user_id = tcvl.bindvalue_id
                        AND p.tracker_id = ?
                        AND p.perm_level >= 2
                    )',
                    [$tracker_id]
                )
            );
        }
        //}}}

        // {{{ project_members
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::PROJECT_MEMBERS, $dynamic_ugroups, $allowed_ugroups)) {
            $sqls[] = $this->getSqlFilterForContributorGroup(
                $from_where,
                $contributor_field_id,
                new ParametrizedFrom(
                    'INNER JOIN user_group AS ug ON (
                        ug.user_id = tcvl.bindvalue_id
                        AND ug.group_id = ?
                    )',
                    [$project_id]
                )
            );
        }
        //}}}

        // {{{ project_admins
        if ($this->hasPermissionForDynamicUgroup(ProjectUGroup::PROJECT_ADMIN, $dynamic_ugroups, $allowed_ugroups)) {
            $sqls[] = $this->getSqlFilterForContributorGroup(
                $from_where,
                $contributor_field_id,
                new ParametrizedFrom(
                    'INNER JOIN user_group AS ug ON (
                        ug.user_id = tcvl.bindvalue_id
                        AND ug.group_id = $project_id
                        AND ug.admin_flags = "A"
                    )',
                    [$project_id]
                )
            );
        }
        //}}}

        return $sqls;
    }

    private function hasPermissionForStaticUgroup(array $static_ugroups, array $allowed_ugroups): bool
    {
        return count(array_intersect($static_ugroups, $allowed_ugroups)) > 0;
    }

    private function hasPermissionForDynamicUgroup(int $ugroup_id, array $dynamic_ugroups, array $allowed_groups): bool
    {
        return in_array($ugroup_id, $dynamic_ugroups) && in_array($ugroup_id, $allowed_groups);
    }

    private function getSqlFilterForSubmittedByGroup(
        IProvideParametrizedFromAndWhereSQLFragments $from_where,
        ParametrizedFrom $join_user_constraint,
    ): ParametrizedQuery {
        return new ParametrizedQuery(
            'SELECT c.artifact_id AS id, c.id AS last_changeset_id',
            new ParametrizedAndFromWhere(
                $from_where,
                ParametrizedFromWhere::fromParametrizedFrom($join_user_constraint),
            ),
        );
    }
}
