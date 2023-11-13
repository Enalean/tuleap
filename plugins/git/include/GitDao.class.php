<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\Git\Repository\Settings\ArtifactClosure\ConfigureAllowArtifactClosure;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;

class GitDao extends \Tuleap\DB\DataAccessObject implements VerifyArtifactClosureIsAllowed, ConfigureAllowArtifactClosure
{
    public const REPOSITORY_ID               = 'repository_id'; //PK
    public const REPOSITORY_NAME             = 'repository_name';
    public const REPOSITORY_PATH             = 'repository_path';
    public const REPOSITORY_DESCRIPTION      = 'repository_description';
    public const REPOSITORY_PARENT           = 'repository_parent_id';
    public const FK_PROJECT_ID               = 'project_id';//FK
    public const REPOSITORY_CREATION_DATE    = 'repository_creation_date';
    public const REPOSITORY_CREATION_USER_ID = 'repository_creation_user_id';
    public const REPOSITORY_DELETION_DATE    = 'repository_deletion_date';
    public const REPOSITORY_IS_INITIALIZED   = 'repository_is_initialized';
    public const REPOSITORY_ACCESS           = 'repository_access';
    public const REPOSITORY_MAIL_PREFIX      = 'repository_events_mailing_prefix';
    public const REPOSITORY_BACKEND_TYPE     = 'repository_backend_type';
    public const REPOSITORY_SCOPE            = 'repository_scope';
    public const REPOSITORY_NAMESPACE        = 'repository_namespace';
    public const REPOSITORY_BACKUP_PATH      = 'repository_backup_path';

    public const REPO_NAME_MAX_LENGTH = 255;

    public const BACKEND_GITOLITE = 'gitolite';

    public const REMOTE_SERVER_ID               = 'remote_server_id';
    public const REMOTE_SERVER_DISCONNECT_DATE  = 'remote_server_disconnect_date';
    public const REMOTE_SERVER_DELETE_DATE      = 'remote_project_deleted_date';
    public const REMOTE_SERVER_MIGRATION_STATUS = 'remote_server_migration_status';

    public const NOT_DELETED_DATE = '0000-00-00 00:00:00';
    public const ORDER_BY_PATH    = 'path';

    public function exists($id)
    {
        if (empty($id)) {
            return false;
        }
        $sql    = 'SELECT repository_id
                FROM plugin_git
                WHERE repository_id = ? AND repository_deletion_date = "0000-00-00 00:00:00"';
        $result = $this->getDB()->run($sql, $id);

        return ! empty($result) && count($result) === 1;
    }

    public function initialize($repositoryId)
    {
        $sql = 'UPDATE plugin_git SET repository_is_initialized = 1 WHERE repository_id = ?';
        try {
            $this->getDB()->run($sql, $repositoryId);
        } catch (PDOException $ex) {
            throw new GitDaoException(dgettext('tuleap-git', 'Unable to update Repository data'));
        }
        return true;
    }

    public function save(GitRepository $repository)
    {
        $id = (int) $repository->getId();

        $name       = $repository->getName();
        $mailPrefix = $repository->getMailPrefix();
        $parentId   = 0;
        $scope      = $repository->getScope();
        $namespace  = $repository->getNamespace();

        try {
            $parent = $repository->getParent();
            if (! empty($parent)) {
                $parentId = $parent->getId();
            }
        } catch (GitDaoException $e) {
        }
        $projectId      = $repository->getProjectId();
        $description    = $repository->getDescription();
        $path           = $repository->getPath();
        $isInitialized  = $repository->getIsInitialized();
        $creationUserId = $repository->getCreatorId();
        $access         = $repository->getAccess();
        $backup_path    = $repository->getBackupPath();

        if ($this->exists($id)) {
            try {
                $this->getDB()->update(
                    'plugin_git',
                    [
                        'repository_description'           => $description,
                        'repository_is_initialized'        => $isInitialized,
                        'repository_access'                => $access,
                        'repository_events_mailing_prefix' => $mailPrefix,
                        'repository_backup_path'           => $backup_path,
                    ],
                    ['repository_id' => $id]
                );
            } catch (PDOException $ex) {
                throw new GitDaoException(dgettext('tuleap-git', 'Unable to update Repository data'));
            }
            return true;
        }

        $repository_backend = $repository->getBackend();
        if ($repository_backend instanceof Git_Backend_Gitolite) {
            $backendType = self::BACKEND_GITOLITE;
        } else {
            throw new \LogicException(sprintf("Unexpected Git backend (%s)", $repository_backend::class));
        }

        try {
            $this->getDB()->insert(
                'plugin_git',
                [
                    'repository_name'             => $name,
                    'repository_path'             => $path,
                    'repository_parent_id'        => $parentId,
                    'repository_description'      => $description,
                    'project_id'                  => $projectId,
                    'repository_creation_date'    => date('Y-m-d H:i:s'),
                    'repository_creation_user_id' => $creationUserId,
                    'repository_is_initialized'   => $isInitialized,
                    'repository_access'           => $access,
                    'repository_backend_type'     => $backendType,
                    'repository_scope'            => $scope,
                    'repository_namespace'        => $namespace,
                    'allow_artifact_closure'      => empty($parentId),
                ]
            );
        } catch (PDOException $ex) {
            throw new GitDaoException(dgettext('tuleap-git', 'Unable to update Repository data'));
        }

        return $this->getDB()->lastInsertId();
    }

    public function delete(GitRepository $repository)
    {
        $id        = $repository->getId();
        $projectId = $repository->getProjectId();
        if (empty($id) || empty($projectId)) {
            throw new GitDaoException(dgettext('tuleap-git', 'Missing repository id or project id'));
        }
        $deletionDate = $repository->getDeletionDate();
        $projectName  = $repository->getProject()->getUnixName();
        $backup_path  = str_replace('/', '_', $repository->getFullName());
        $backup_path .= '_' . strtotime($deletionDate);
        $backup_path  = $projectName . '_' . $backup_path;

        try {
            $affected_rows = $this->getDB()->update(
                'plugin_git',
                [
                    'repository_deletion_date' => $deletionDate,
                    'repository_backup_path'   => $backup_path,
                ],
                [
                    'repository_id' => $id,
                    'project_id'    => $projectId,
                ]
            );
        } catch (PDOException $ex) {
            return false;
        }

        return $affected_rows === 1;
    }

    public function renameProject(Project $project, $newName)
    {
        $oldPath = $project->getUnixName() . '/';
        $newPath = $newName . '/';

        try {
            $this->getDB()->run(
                'UPDATE plugin_git
                SET repository_path = REPLACE(repository_path, ?, ?)
                WHERE project_id = ?',
                $oldPath,
                $newPath,
                $project->getID()
            );
        } catch (PDOException $ex) {
            return false;
        }

        return true;
    }

    /**
     * Obtain project's list of git repositories. May be filtered out by user to get only her own repositories
     *
     * @param int $projectId    Project id
     * @param bool $scope Allows to get all projects ignoring if the scope is project or personal
     * @param int $userId User id
     *
     * @TODO: Add a way to obtain all project repositories including both project scope & user scope
     *
     * @return Array
     */
    public function getProjectRepositoryList($projectId, $scope = true, $userId = null)
    {
        $condition = EasyStatement::open();
        $condition->andWith('project_id = ?', $projectId);
        $condition->andWith('repository_deletion_date = ?', '0000-00-00 00:00:00');

        if (empty($projectId)) {
            return [];
        }

        if ($scope) {
            if (empty($userId)) {
                $condition->andWith('repository_scope = ?', GitRepository::REPO_SCOPE_PROJECT);
            } else {
                $condition->andWith('repository_creation_user_id = ? AND repository_scope = ?', $userId, GitRepository::REPO_SCOPE_INDIVIDUAL);
            }
        }

        $sql = "SELECT * FROM plugin_git
                WHERE $condition
                ORDER BY CONCAT(repository_namespace, repository_name)";

        $results = $this->getDB()->safeQuery(
            $sql,
            $condition->values()
        );
        assert(is_array($results));

        $list = [];
        foreach ($results as $row) {
            $repo_id        = $row['repository_id'];
            $list[$repo_id] = $row;
        }

        return $list;
    }

    public function getActiveRepositoryPathsWithRemoteServersForAllProjects()
    {
        $sql = "SELECT * FROM plugin_git
                WHERE remote_server_id IS NOT NULL
                AND repository_deletion_date = '0000-00-00 00:00:00'";

        return $this->getDB()->run($sql);
    }

    /**
     * Return the list of users that owns repositories in the project $projectId
     */
    public function getProjectRepositoriesOwners($projectId)
    {
        $sql = "SELECT DISTINCT repository_creation_user_id, user_name, realname
                FROM plugin_git
                    INNER JOIN user ON user.user_id = repository_creation_user_id
                WHERE project_id = ?
                  AND repository_deletion_date = '0000-00-00 00:00:00'
                  AND repository_scope = 'I'
                ORDER BY user.user_name";

        return $this->getDB()->run($sql, $projectId);
    }

    public function getAllGitoliteRespositories($projectId)
    {
        $sql = "SELECT * FROM plugin_git
                WHERE project_id = ?
                  AND repository_deletion_date = '0000-00-00 00:00:00'
                  AND repository_backend_type = ?";

        return $this->getDB()->run($sql, $projectId, self::BACKEND_GITOLITE);
    }

    /**
     * This function initialize a GitRepository object with its database value
     * @param GitRepository $repository
     * @return true
     */
    public function getProjectRepository($repository)
    {
        $projectId      = $repository->getProjectId();
        $repositoryPath = $repository->getPathWithoutLazyLoading();
        if (empty($projectId) || empty($repositoryPath)) {
            throw new GitDaoException(dgettext('tuleap-git', 'Repository not found : missing repository name or project id'));
        }
        $row = $this->searchProjectRepositoryByPath($projectId, $repositoryPath);
        if (empty($row)) {
            throw new GitDaoException(dgettext('tuleap-git', 'Repository not found'));
        }
        $this->hydrateRepositoryObject($repository, $row);
        return true;
    }

    public function searchProjectRepositoryByPath($projectId, $repositoryPath)
    {
        // BINARY ? used in query in order to create a case sensitive match
        return $this->getDB()->row(
            'SELECT * FROM plugin_git
                       WHERE repository_path = BINARY ? AND project_id = ?
                            AND repository_deletion_date = "0000-00-00 00:00:00"',
            $repositoryPath,
            $projectId
        );
    }

    public function hasChild($repository)
    {
        $repoId = $repository->getId();
        if (empty($repoId)) {
            throw new GitDaoException(dgettext('tuleap-git', 'Repository child search failed : missing repository id'));
        }
        $query = 'SELECT repository_id' .
                 ' FROM plugin_git' .
                 ' WHERE repository_parent_id=? AND repository_deletion_date="0000-00-00 00:00:00"';

        try {
            $result = $this->getDB()->run($query, $repoId);
        } catch (PDOException $ex) {
            return false;
        }
        return ! empty($result);
    }

    /**
     * This function log a Git Push in the database
     *
     * @param int $repoId Id of the git repository
     * @param int $userId Id of the user that performed the push
     * @param int $pushTimestamp Date of the push
     * @param int $commitsNumber Number of commits
     *
     * @return bool
     */
    public function logGitPush($repoId, $userId, $pushTimestamp, $commitsNumber, $refname, $operation_type, $refname_type)
    {
        try {
            $this->getDB()->insert(
                'plugin_git_log',
                [
                    'repository_id'  => $repoId,
                    'user_id'        => $userId,
                    'push_date'      => $pushTimestamp,
                    'commits_number' => $commitsNumber,
                    'refname'        => $refname,
                    'operation_type' => $operation_type,
                    'refname_type'   => $refname_type,
                ]
            );
        } catch (PDOException $ex) {
            return false;
        }
        return true;
    }

    public function getProjectRepositoryById($repository)
    {
        $id = (int) $repository->getId();
        if (empty($id)) {
            return false;
        }
        $row = $this->searchProjectRepositoryById($id);
        if (empty($row)) {
            throw new GitDaoException(dgettext('tuleap-git', 'Repository not found'));
        }
        $this->hydrateRepositoryObject($repository, $row);
        return true;
    }

    /**
     * @return array
     */
    public function searchProjectRepositoryById(int $id)
    {
        return $this->getDB()->row(
            'SELECT * FROM plugin_git WHERE repository_id = ? AND repository_deletion_date = "0000-00-00 00:00:00"',
            $id
        );
    }

    public function searchDeletedRepositoryById($id)
    {
        return $this->getDB()->row(
            'SELECT * FROM plugin_git WHERE repository_id = ? AND repository_deletion_date != "0000-00-00 00:00:00"',
            $id
        );
    }

    /**
     * Retrieve Git repository data given its name and its group name.
     *
     * @param String $repositoryName Name of the repository we are looking for.
     * @param String $projectId      ID of the project to which the repository belong.
     *
     */
    public function getProjectRepositoryByName($repositoryName, $projectId)
    {
        return $this->getDB()->single(
            'SELECT * FROM plugin_git
                       WHERE repository_name = ? AND project_id = ? AND repository_deletion_date = "0000-00-00 00:00:00"',
            [$repositoryName, $projectId]
        );
    }

    /**
     * @deprecated Should use GitRepository::getInstanceFrom row instead.
     * @param type $result
     */
    public function hydrateRepositoryObject(GitRepository $repository, $result)
    {
        $repository->setName($result[self::REPOSITORY_NAME]);
        $repository->setPath($result[self::REPOSITORY_PATH]);
        $repository->setId($result[self::REPOSITORY_ID]);
        $repository->setDescription($result[self::REPOSITORY_DESCRIPTION]);
        $repository->setParentId($result[self::REPOSITORY_PARENT]);
        $project = ProjectManager::instance()->getProject($result[self::FK_PROJECT_ID]);
        $repository->setProject($project);
        $repository->setCreationDate($result[self::REPOSITORY_CREATION_DATE]);
        $user = UserManager::instance()->getUserById($result[self::REPOSITORY_CREATION_USER_ID]);
        $repository->setCreator($user);
        $repository->setIsInitialized($result[self::REPOSITORY_IS_INITIALIZED]);
        $repository->setDeletionDate($result[self::REPOSITORY_DELETION_DATE]);
        $repository->setAccess($result[self::REPOSITORY_ACCESS]);
        $repository->setMailPrefix($result[self::REPOSITORY_MAIL_PREFIX]);
        $repository->setBackendType($result[self::REPOSITORY_BACKEND_TYPE]);
        $repository->setNamespace($result[self::REPOSITORY_NAMESPACE]);
        $repository->setBackupPath($result[self::REPOSITORY_BACKUP_PATH]);
        $repository->setScope($result[self::REPOSITORY_SCOPE]);
        $repository->setRemoteServerId($result[self::REMOTE_SERVER_ID]);
        $repository->setRemoteServerDisconnectDate($result[self::REMOTE_SERVER_DISCONNECT_DATE]);
        $repository->setRemoteProjectDeletionDate($result[self::REMOTE_SERVER_DELETE_DATE]);
        $repository->setRemoteServerMigrationStatus($result[self::REMOTE_SERVER_MIGRATION_STATUS]);
        if (isset($result['push_date'])) {
            $repository->setLastPushDate($result['push_date']);
        }
    }

    /**
     * Count number of repositories grouped by backend type
     *
     * @param String  $startDate   Start date
     * @param String  $endDate     End date
     * @param int $projectId Project Id
     * @param bool $stillActive Select only reposirtories that still active
     */
    public function getBackendStatistics($backend, $startDate, $endDate, $projectId = null, $stillActive = false)
    {
        $condition = EasyStatement::open();
        $condition->andWith('repository_backend_type = ?', $backend);
        $condition->andWith('repository_creation_date BETWEEN CAST(? AS DATETIME) AND CAST(? AS DATETIME)', $startDate, $endDate);
        if ($projectId) {
            $condition->andWith('project_id = ?', $projectId);
        }
        if ($stillActive) {
            $condition->andWith('status = "A" AND repository_deletion_date = "0000-00-00 00:00:00"');
        }
        $query = "SELECT count(repository_id) AS count,
                  YEAR(repository_creation_date) AS year,
                  MONTHNAME(STR_TO_DATE(MONTH(repository_creation_date), '%m')) AS month
                  FROM plugin_git
                  JOIN `groups` AS g ON group_id = project_id
                  WHERE $condition
                  GROUP BY year, month
                  ORDER BY year, STR_TO_DATE(month,'%M')";

        return $this->getDB()->safeQuery($query, $condition->values());
    }

    public function isRepositoryExisting($project_id, $path)
    {
        $sql = "SELECT TRUE
                FROM plugin_git
                WHERE repository_path = ?
                  AND project_id = ?
                  AND repository_deletion_date = '0000-00-00 00:00:00'
                LIMIT 1";

        return (bool) $this->getDB()->single($sql, [$path, $project_id]);
    }

    /**
     *
     * @param int $repository_id
     * @param int $remote_server_id
     *
     * @return bool
     */
    public function switchToGerrit($repository_id, $remote_server_id)
    {
        $sql = 'UPDATE plugin_git
                SET remote_server_id = ?,
                    remote_server_disconnect_date = NULL,
                    remote_project_deleted_date = NULL
                WHERE repository_id = ?';

        try {
            $this->getDB()->run($sql, $remote_server_id, $repository_id);
        } catch (PDOException $ex) {
            return false;
        }
        return true;
    }

    public function setGerritMigrationError($repository_id)
    {
        $sql = "UPDATE plugin_git
                SET remote_server_migration_status = 'ERROR'
                WHERE repository_id = ?";
        $this->getDB()->run($sql, $repository_id);
    }

    public function setGerritMigrationSuccess($repository_id)
    {
        $sql = "UPDATE plugin_git
                SET remote_server_migration_status = 'DONE'
                WHERE repository_id = ?";
        $this->getDB()->run($sql, $repository_id);
    }

    public function disconnectFromGerrit($repository_id)
    {
        $sql = 'UPDATE plugin_git
                SET remote_server_disconnect_date = UNIX_TIMESTAMP(), remote_server_migration_status = NULL
                WHERE repository_id = ?';
        $this->getDB()->run($sql, $repository_id);
    }

    /**
     * @return bool
     */
    public function setGerritProjectAsDeleted($repository_id)
    {
        $sql = 'UPDATE plugin_git
                SET remote_project_deleted_date = UNIX_TIMESTAMP(), remote_server_migration_status = NULL
                WHERE repository_id = ?';
        try {
            $this->getDB()->run($sql, $repository_id);
        } catch (PDOException $ex) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isRemoteServerUsed($remote_server_id)
    {
        $sql  = 'SELECT NULL
                FROM plugin_git
                    JOIN `groups` ON (group_id = project_id)
                WHERE remote_server_id = ?
                    AND remote_server_disconnect_date IS NULL
                    AND `groups`.status IN ("A", "s")
                LIMIT 1';
        $rows = $this->getDB()->run($sql, $remote_server_id);
        return count($rows) > 0;
    }

    /**
     * @return bool
     */
    public function isRemoteServerUsedInProject($remote_server_id, $project_id)
    {
        $sql = 'SELECT NULL
                FROM plugin_git
                WHERE remote_server_id = ?
                    AND remote_server_disconnect_date IS NULL
                    AND project_id = ?
                LIMIT 1';

        $rows = $this->getDB()->run($sql, $remote_server_id, $project_id);

        return count($rows) > 0;
    }

    public function searchGerritRepositoriesWithPermissionsForUGroupAndProject($project_id, $ugroup_ids)
    {
        $permission_type_condition = EasyStatement::open()->in('?*', [Git::PERM_READ, Git::PERM_WRITE, Git::PERM_WPLUS]);
        $ugroup_ids_condition      = EasyStatement::open()->in('?*', $ugroup_ids);
        $sql                       = "SELECT *
                FROM plugin_git git
                  JOIN permissions ON (
                    permissions.object_id = CAST(git.repository_id as CHAR CHARACTER SET utf8)
                    AND permissions.permission_type IN ($permission_type_condition)
                    )
                WHERE git.remote_server_id IS NOT NULL
                  AND git.project_id = ?
                  AND permissions.ugroup_id IN ($ugroup_ids_condition)";

        return $this->getDB()->safeQuery(
            $sql,
            array_merge($permission_type_condition->values(), [$project_id], $ugroup_ids_condition->values())
        );
    }

    public function searchAllGerritRepositoriesOfProject($project_id)
    {
        $sql = 'SELECT *
                FROM plugin_git
                WHERE remote_server_id IS NOT NULL
                  AND project_id = ?';

        return $this->getDB()->run($sql, $project_id);
    }

    /**
     * @param array $repository_ids
     */
    public function searchRepositoriesInSameProjectFromRepositoryList(array $repository_ids, $project_id)
    {
        $repository_list_condition = EasyStatement::open();
        $repository_list_condition->in('repository_id IN(?*)', $repository_ids);

        $sql = "SELECT repository_id FROM plugin_git
                WHERE $repository_list_condition AND project_id = ?";

        return $this->getDB()->safeQuery($sql, array_merge($repository_list_condition->values(), [$project_id]));
    }

    /**
     * Get the list of all deleted Git repositories to be purged
     *
     * @param int $retention_period
     */
    public function getDeletedRepositoriesToPurge($retention_period)
    {
        $query = 'SELECT * ' .
                 ' FROM plugin_git' .
                 ' WHERE repository_deletion_date != "0000-00-00 00:00:00"' .
                 ' AND repository_backend_type = ?' .
                 ' AND TO_DAYS(NOW()) - TO_DAYS(repository_deletion_date) = ?';

        return $this->getDB()->run($query, self::BACKEND_GITOLITE, $retention_period);
    }

    /**
     * Get the list of all deleted Git repositories of a given project
     *
     * @param Int $project_id
     * @param Int $retention_period
     */
    public function getDeletedRepositoriesByProjectId($project_id, $retention_period)
    {
        $query = 'SELECT * ' .
                 ' FROM plugin_git' .
                 ' WHERE repository_deletion_date != "0000-00-00 00:00:00"' .
                 ' AND repository_backend_type = ?' .
                 ' AND project_id = ?' .
                 ' AND TO_DAYS(NOW()) - TO_DAYS(repository_deletion_date) < ?' .
                 ' AND repository_id NOT IN (' .
                 '     SELECT parameters FROM system_event' .
                 '     WHERE type=? ' .
                 '     AND status IN (?, ?))';

        return $this->getDB()->run(
            $query,
            self::BACKEND_GITOLITE,
            $project_id,
            $retention_period,
            SystemEvent_GIT_REPO_RESTORE::NAME,
            SystemEvent::STATUS_NEW,
            SystemEvent::STATUS_RUNNING
        );
    }

    /**
     * Activate deleted repository
     *
     * @param Int $repository_id
     *
     * @return true
     */
    public function activate($repository_id)
    {
        $sql = 'UPDATE plugin_git
                SET repository_deletion_date = "0000-00-00 00:00:00", repository_backup_path = ""
                WHERE repository_id = ?';
        $this->getDB()->run($sql, $repository_id);

        return true;
    }

    public function getPaginatedOpenRepositories($project_id, $scope, $owner_id, $order_by, $limit, $offset)
    {
        $additional_where_statement = EasyStatement::open();

        $additional_where_statement->with('repository_deletion_date IS NULL');
        $additional_where_statement->andWith('project_id = ?', $project_id);

        if ($scope) {
            $additional_where_statement->andWith('repository_scope = ?', $scope);
        }

        if ($owner_id) {
            $additional_where_statement->andWith('repository_creation_user_id = ?', $owner_id);
        }

        $limit_parameters = [$limit, $offset];
        $limit_statement  = "LIMIT ? OFFSET ?";
        $order            = "push_date DESC";
        if ($order_by === self::ORDER_BY_PATH) {
            $order            = "git.repository_name ASC";
            $limit_parameters = [];
            $limit_statement  = "";
        }
        $query_parameters = array_merge($additional_where_statement->values(), $limit_parameters);

        $sql = "SELECT SQL_CALC_FOUND_ROWS
                  git.repository_id,
                  git.repository_name,
                  git.repository_description,
                  git.repository_path,
                  git.repository_parent_id,
                  git.project_id,
                  git.repository_creation_user_id,
                  git.repository_creation_date,
                  git.repository_deletion_date,
                  git.repository_is_initialized,
                  git.repository_access,
                  git.repository_events_mailing_prefix,
                  git.repository_backend_type,
                  git.repository_scope,
                  git.repository_namespace,
                  git.repository_backup_path,
                  git.remote_server_id,
                  git.remote_server_disconnect_date,
                  git.remote_project_deleted_date,
                  git.remote_server_migration_status,
                  IF(plugin_git_log.push_date, MAX(plugin_git_log.push_date),UNIX_TIMESTAMP(git.repository_creation_date)) as push_date
                FROM plugin_git AS git
                  LEFT JOIN plugin_git_log ON plugin_git_log.repository_id = git.repository_id
                WHERE
                  $additional_where_statement
                GROUP BY git.repository_id,
                  git.repository_name,
                  git.repository_description,
                  git.repository_path,
                  git.repository_parent_id,
                  git.project_id,
                  git.repository_creation_user_id,
                  git.repository_creation_date,
                  git.repository_deletion_date,
                  git.repository_is_initialized,
                  git.repository_access,
                  git.repository_events_mailing_prefix,
                  git.repository_backend_type,
                  git.repository_scope,
                  git.repository_namespace,
                  git.repository_backup_path,
                  git.remote_server_id,
                  git.remote_server_disconnect_date,
                  git.remote_project_deleted_date,
                  git.remote_server_migration_status
                ORDER BY $order
                $limit_statement";

        // The repository creation date is a DATETIME value so before converting to a timestamp we need to
        // put MySQL in the same timezone than the one PHP used to create the date
        $current_mysql_timezone = $this->getDB()->single('SELECT @@session.time_zone');
        try {
            $this->getDB()->run('SET time_zone = ?', date_default_timezone_get());
        } catch (PDOException $ex) {
            // Changing the time_zone of the current MySQL session might fail if the time zone tables are not
            // populated or not in sync with the PHP timezones. In that case we just silent the issue and repositories
            // without any push might have an incorrect 'push_date' value if the MySQL server does not use the same
            // timezone than the session of PHP-FPM that has registered the value 'repository_creation_date'
        }
        try {
            $results = $this->getDB()->safeQuery($sql, $query_parameters);

            if ($order_by === self::ORDER_BY_PATH) {
                $sorter = new \Tuleap\Git\RepositoryList\DaoByRepositoryPathSorter();
                return array_slice($sorter->sort($results), $offset, $limit);
            }

            return $results;
        } finally {
            $this->getDB()->run('SET time_zone = ?', $current_mysql_timezone);
        }
    }

    public function getUGroupsByRepositoryPermissions($repository_id, $permission_type)
    {
        $sql = 'SELECT ugroup_id
                FROM permissions
                WHERE permission_type = ?
                AND object_id = CAST(? AS CHAR CHARACTER SET utf8)';

        $rows = $this->getDB()->run($sql, $permission_type, $repository_id);

        if (! empty($rows)) {
            return $rows;
        }

        return $this->getDefaultPermissions($permission_type);
    }

    private function getDefaultPermissions($permission_type)
    {
        $default_sql = "SELECT ugroup_id
                        FROM permissions_values
                        WHERE permission_type = ?
                            AND is_default = 1";

        return $this->getDB()->run($default_sql, $permission_type);
    }

    public function getForksOfRepositoryForUser($repository_id, $user_id)
    {
        $sql = "SELECT *
                FROM plugin_git
                WHERE repository_parent_id = ?
                  AND repository_creation_user_id = ?
                  AND repository_scope = 'I'
                  AND repository_deletion_date IS NULL";

        return $this->getDB()->run($sql, $repository_id, $user_id);
    }

    public function searchRepositoriesActiveInTheLast2Months()
    {
        $sql = 'SELECT DISTINCT plugin_git.*
                FROM plugin_git
                JOIN plugin_git_log ON plugin_git_log.repository_id = plugin_git.repository_id
                WHERE repository_deletion_date = "0000-00-00 00:00:00" AND
                      push_date > UNIX_TIMESTAMP(NOW() - INTERVAL 2 MONTH)';

        return $this->getDB()->run($sql);
    }

    public function isArtifactClosureAllowed(int $repository_id): bool
    {
        $sql = 'SELECT repo.allow_artifact_closure FROM plugin_git AS repo WHERE repo.repository_id = ?';

        $result = $this->getDB()->cell($sql, $repository_id);
        return $result === 1;
    }

    public function allowArtifactClosureForRepository(int $repository_id): void
    {
        $this->getDB()->update(
            'plugin_git',
            ['allow_artifact_closure' => true],
            ['repository_id' => $repository_id]
        );
    }

    public function forbidArtifactClosureForRepository(int $repository_id): void
    {
        $this->getDB()->update(
            'plugin_git',
            ['allow_artifact_closure' => false],
            ['repository_id' => $repository_id]
        );
    }
}
