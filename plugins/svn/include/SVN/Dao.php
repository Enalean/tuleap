<?php
/**
  * Copyright (c) Enalean, 2016 - Present. All rights reserved
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
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

namespace Tuleap\SVN;

use DataAccessObject;
use SystemEvent;
use Tuleap\SVN\Events\SystemEvent_SVN_RESTORE_REPOSITORY;
use Tuleap\SVN\Repository\CoreRepository;
use Tuleap\SVNCore\Repository;
use Project;

class Dao extends DataAccessObject
{
    public function searchByProject(Project $project)
    {
        $project_id = $this->da->escapeInt($project->getId());
        $sql        = 'SELECT *
                FROM plugin_svn_repositories
                LEFT JOIN plugin_svn_last_access
                  ON plugin_svn_repositories.id = plugin_svn_last_access.repository_id
                WHERE project_id=' . $project_id . '
                AND repository_deletion_date IS NULL
                ORDER BY name ASC';

        return $this->retrieve($sql);
    }

    public function searchPaginatedByProject(Project $project, $limit, $offset)
    {
        $project_id = $this->da->escapeInt($project->getId());
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_svn_repositories
                WHERE project_id = $project_id
                  AND repository_deletion_date IS NULL
                ORDER BY name ASC
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function searchPaginatedByProjectAndByName(Project $project, $repository_name, $limit, $offset)
    {
        $project_id      = $this->da->escapeInt($project->getId());
        $limit           = $this->da->escapeInt($limit);
        $offset          = $this->da->escapeInt($offset);
        $repository_name = $this->da->quoteSmart($repository_name);

        $sql = "SELECT SQL_CALC_FOUND_ROWS *
                FROM plugin_svn_repositories
                WHERE project_id = $project_id
                  AND repository_deletion_date IS NULL
                  AND name = $repository_name
                ORDER BY name ASC
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function searchByRepositoryIdAndProjectId($id, Project $project)
    {
        $id         = $this->da->escapeInt($id);
        $project_id = $this->da->escapeInt($project->getId());
        $sql        = "SELECT *
                FROM plugin_svn_repositories
                WHERE id=$id AND project_id=$project_id";

        return $this->retrieveFirstRow($sql);
    }

    public function doesRepositoryAlreadyExist($name, Project $project)
    {
        $name       = $this->da->quoteSmart($name);
        $project_id = $this->da->escapeInt($project->getId());
        $sql        = "SELECT *
                FROM plugin_svn_repositories
                WHERE name=$name AND project_id=$project_id
                LIMIT 1";

        return count($this->retrieve($sql)) > 0;
    }

    /**
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface|false
     * @throws \DataAccessQueryException
     */
    public function getAllRepositoriesInActiveProjects()
    {
        $sql = "SELECT `groups`.*, plugin_svn_repositories.*
                FROM `groups`
                    INNER JOIN service ON (service.group_id = `groups`.group_id AND service.short_name = 'plugin_svn')
                    INNER JOIN plugin_svn_repositories ON (plugin_svn_repositories.project_id = `groups`.group_id)
                WHERE service.is_used = '1'
                  AND `groups`.status = 'A'
                  AND repository_deletion_date IS NULL";

        return $this->retrieve($sql);
    }

    public function searchRepositoryByName(Project $project, $name)
    {
        $project_name = $this->da->quoteSmart($project->getUnixNameMixedCase());
        $name         = $this->da->quoteSmart($name);

        $sql = "SELECT `groups`.*, id, name, CONCAT(unix_group_name, '/', name) AS repository_name,
                    backup_path, repository_deletion_date, is_core
                FROM `groups`, plugin_svn_repositories
                WHERE project_id = `groups`.group_id
                AND `groups`.unix_group_name = $project_name
                AND plugin_svn_repositories.name = $name";

        return $this->retrieveFirstRow($sql);
    }

    public function create(Repository $repository)
    {
        $name       = $this->da->quoteSmart($repository->getName());
        $project_id = $this->da->escapeInt($repository->getProject()->getId());
        $is_core    = $repository instanceof CoreRepository ? '1' : '0';

        $query = "INSERT INTO plugin_svn_repositories
            (name,  project_id, is_core) values ($name, $project_id, $is_core)";

        return $this->updateAndGetLastId($query);
    }

    public function markAsDeleted($repository_id, $backup_path, $deletion_date)
    {
        if ($deletion_date) {
            $backup_path = $this->da->quoteSmart($backup_path);
        } else {
            $backup_path = "NULL";
        }
        $repository_id = $this->da->escapeInt($repository_id);

        if ($deletion_date) {
            $deletion_date = $this->da->quoteSmart($deletion_date);
        } else {
            $deletion_date = "NULL";
        }

        $sql = "UPDATE plugin_svn_repositories SET
                    repository_deletion_date = $deletion_date,
                    backup_path = $backup_path
                WHERE id = $repository_id";

        return $this->update($sql);
    }

    public function getDeletedRepositoriesToPurge($retention_date)
    {
        $retention_date = $this->da->escapeInt($retention_date);
        $sql            = "SELECT *
                  FROM plugin_svn_repositories
                  WHERE repository_deletion_date IS NOT NULL
                    AND FROM_UNIXTIME(repository_deletion_date) <= FROM_UNIXTIME($retention_date)";

        return $this->retrieve($sql);
    }

    public function getRestorableRepositoriesByProject($project_id)
    {
        $project_id     = $this->da->escapeInt($project_id);
        $svn_type       = $this->da->quoteLikeValuePrefix(SystemEvent_SVN_RESTORE_REPOSITORY::NAME);
        $status_new     = $this->da->quoteSmart(SystemEvent::STATUS_NEW);
        $status_running = $this->da->quoteSmart(SystemEvent::STATUS_RUNNING);

        $sql = "SELECT plugin_svn_repositories.*
                FROM plugin_svn_repositories
                LEFT JOIN system_event ON CONCAT(project_id, '::', plugin_svn_repositories.id) = parameters
                      AND system_event.type LIKE $svn_type
                      AND system_event.status IN ($status_new, $status_running)
                WHERE parameters IS NULL
                  AND project_id = $project_id
                  AND repository_deletion_date IS NOT NULL";

         return $this->retrieve($sql);
    }

    public function delete($repository_id)
    {
        $repository_id = $this->da->escapeInt($repository_id);

        $this->da->startTransaction();

        $sql = "DELETE
                FROM plugin_svn_accessfile_history
                WHERE repository_id = $repository_id";
        if (! $this->update($sql)) {
            $this->da->rollback();
            return false;
        }

        $sql = "DELETE
                FROM plugin_svn_immutable_tag
                WHERE repository_id = $repository_id";
        if (! $this->update($sql)) {
            $this->da->rollback();
            return false;
        }

        $sql = "DELETE
                FROM plugin_svn_mailing_header
                WHERE repository_id = $repository_id";
        if (! $this->update($sql)) {
            $this->da->rollback();
            return false;
        }

        $sql = "DELETE n.*, u.*, g.*
                FROM plugin_svn_notification AS n
                    LEFT JOIN plugin_svn_notification_users AS u ON(n.id = u.notification_id)
                    LEFT JOIN plugin_svn_notification_ugroups AS g ON(n.id = g.notification_id)
                WHERE n.repository_id = $repository_id";
        if (! $this->update($sql)) {
            $this->da->rollback();
            return false;
        }

        $sql = "DELETE
                FROM plugin_svn_hook_config
                WHERE repository_id = $repository_id";
        if (! $this->update($sql)) {
            $this->da->rollback();
            return false;
        }

        $sql = "DELETE
                FROM plugin_svn_repositories
                WHERE id = $repository_id";

        if (! $this->update($sql)) {
            $this->da->rollback();
            return false;
        }

        return $this->da->commit();
    }

    public function searchByRepositoryId($id)
    {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM plugin_svn_repositories
                WHERE id=$id";

        return $this->retrieveFirstRow($sql);
    }

    public function countSVNCommits()
    {
        $sql = "SELECT sum(svn_write_operations) AS nb
                FROM plugin_svn_full_history";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    public function countSVNCommitBefore(int $timestamp)
    {
        $timestamp = $this->da->escapeInt($timestamp);
        $sql       = "SELECT sum(svn_write_operations) AS nb
                FROM plugin_svn_full_history
                WHERE UNIX_TIMESTAMP(STR_TO_DATE(day, '%Y%m%d'))  >= $timestamp";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    public function searchRepositoriesOfNonDeletedProjects()
    {
        $status_deleted = $this->da->quoteSmart(Project::STATUS_DELETED);

        $sql = 'SELECT plugin_svn_repositories.*
                FROM plugin_svn_repositories
                    INNER JOIN `groups` AS project
                    ON plugin_svn_repositories.project_id = project.group_id
                WHERE project.status <> ' . $status_deleted . '
                AND repository_deletion_date IS NULL
                ORDER BY project_id, name ASC';

        return $this->retrieve($sql);
    }

    public function getCoreRepositoryId(Project $project): ?int
    {
        $sql = sprintf('SELECT id FROM plugin_svn_repositories WHERE project_id = %d and is_core = 1', $this->da->escapeInt($project->getID()));
        $dar = $this->retrieve($sql);
        if ($dar && count($dar) === 1) {
            $row = $dar->getRow();
            return (int) $row['id'];
        }
        return null;
    }
}
