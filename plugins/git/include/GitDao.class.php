<?php
/**
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
  *
  * This file is a part of Codendi.
  *
  * Codendi is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Codendi is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Codendi. If not, see <http://www.gnu.org/licenses/
  */
require_once('common/dao/include/DataAccessObject.class.php');
require_once('exceptions/GitDaoException.class.php');
require_once('common/project/ProjectManager.class.php');
require_once('common/user/UserManager.class.php');
require_once('GitRepository.class.php');
/**
 * Description of GitDaoclass
 * @todo change date format to timestamp instead of mysql date format
 * @author Guillaume Storchi
 */
class GitDao extends DataAccessObject {

    protected $tableName              = 'plugin_git';
    const REPOSITORY_ID               = 'repository_id'; //PK
    const REPOSITORY_NAME             = 'repository_name';
    const REPOSITORY_PATH             = 'repository_path';
    const REPOSITORY_DESCRIPTION      = 'repository_description';
    const REPOSITORY_PARENT           = 'repository_parent_id';
    const FK_PROJECT_ID               = 'project_id';//FK
    const REPOSITORY_CREATION_DATE    = 'repository_creation_date';
    const REPOSITORY_CREATION_USER_ID = 'repository_creation_user_id';
    const REPOSITORY_DELETION_DATE    = 'repository_deletion_date';
    const REPOSITORY_IS_INITIALIZED   = 'repository_is_initialized';
    const REPOSITORY_ACCESS           = 'repository_access';
    const REPOSITORY_MAIL_PREFIX      = 'repository_events_mailing_prefix';
    const REPOSITORY_BACKEND_TYPE     = 'repository_backend_type';
    const REPOSITORY_SCOPE            = 'repository_scope';
    const REPOSITORY_NAMESPACE        = 'repository_namespace';

    const REPO_NAME_MAX_LENGTH = 255;

    const BACKEND_GITSHELL = 'gitshell';
    const BACKEND_GITOLITE = 'gitolite';
    const REMOTE_SERVER_ID = 'remote_server_id';

    public function __construct() {
        parent::__construct( CodendiDataAccess::instance() );
    }

    public function getTable() {
        return $this->tableName;
    }

    public function setTable($tableName) {
        $this->tableName = $tableName;
    }
    
    public function exists($id) {
        if ( empty($id) ) {
            return false;
        }
        $id    = $this->da->escapeInt($id);
        $query = 'SELECT '.self::REPOSITORY_ID.' FROM '.$this->getTable().
                ' WHERE '.self::REPOSITORY_ID.'='.$id.
                    ' AND '.self::REPOSITORY_DELETION_DATE.'='."'0000-00-00 00:00:00'";
        $rs    = $this->retrieve($query);
        if( !empty($rs) && $rs->rowCount() == 1 ) {            
            return true;
        }
        return false;
    }

    public function initialize($repositoryId) {
        $id = $this->da->escapeInt($repositoryId);
        $query = ' UPDATE '.$this->getTable().
                 '  SET '.self::REPOSITORY_IS_INITIALIZED.'=1'.
                 ' WHERE '.self::REPOSITORY_ID.'='.$id;
        if ( $this->update($query) === false ) {
            throw new GitDaoException( $GLOBALS['Language']->getText('plugin_git', 'dao_update_error').' : '.$this->da->isError());
        }
        return true;
    }    

    public function save($repository) {
        $id          = (int)$repository->getId();

        $name        = $repository->getName(); 
        $mailPrefix  = $repository->getMailPrefix();
        $parentId    = 0;
        $scope       = $repository->getScope();
        $namespace   = $repository->getNamespace();
        
        try {
            $parent   = $repository->getParent();
            if ( !empty($parent) ) {
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
        //protect parameters
        $id             = $this->da->escapeInt($id);
        $name           = $this->da->quoteSmart($name);
        $description    = $this->da->quoteSmart($description);
        $path           = $this->da->quoteSmart($path);
        $projectId      = $this->da->escapeInt($projectId);
        $isInitialized  = $this->da->escapeInt($isInitialized);
        $creationUserId = $this->da->escapeInt($creationUserId);
        $access         = $this->da->quoteSmart($access);
        $mailPrefix     = $this->da->quoteSmart($mailPrefix);
        $scope          = $this->da->quoteSmart($scope);
        $namespace      = $this->da->quoteSmart($namespace);
        
        $insert         = false;
        if ( $this->exists($id) ) {            
            $query = 'UPDATE '.$this->getTable().
                     ' SET '.self::REPOSITORY_DESCRIPTION.'='.$description.','.
                            self::REPOSITORY_IS_INITIALIZED.'='.$isInitialized.','.
                            self::REPOSITORY_ACCESS.'='.$access.','.
                            self::REPOSITORY_MAIL_PREFIX.'='.$mailPrefix.' '.
                     'WHERE '.self::REPOSITORY_ID.'='.$id;
        } else {
            if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
                $backendType = self::BACKEND_GITOLITE;
            } else {
                $backendType = self::BACKEND_GITSHELL;
            }
            $insert       = true;
            $creationDate = date('Y-m-d H:i:s');
            $query = 'INSERT INTO '.$this->getTable().'('.self::REPOSITORY_NAME.','.
                                                         self::REPOSITORY_PATH.','.
                                                         self::REPOSITORY_PARENT.','.
                                                         self::REPOSITORY_DESCRIPTION.','.
                                                         self::FK_PROJECT_ID.','.
                                                         self::REPOSITORY_CREATION_DATE.','.
                                                         self::REPOSITORY_CREATION_USER_ID.','.
                                                         self::REPOSITORY_IS_INITIALIZED.','.
                                                         self::REPOSITORY_ACCESS.','.
                                                         self::REPOSITORY_BACKEND_TYPE.','.
                                                         self::REPOSITORY_SCOPE.','.
                                                         self::REPOSITORY_NAMESPACE.
                                                    ') values ('.
                                                        "".$name.",".
                                                        "".$path.",".
                                                        "".$parentId.",".
                                                        "".$description.",".
                                                        $projectId.",".
                                                        "'".$creationDate."',".
                                                        $creationUserId.",".
                                                        $isInitialized.','.
                                                        $access.','.                    
                                                        $this->da->quoteSmart($backendType).','.
                                                        $scope.','.
                                                        $namespace.
                                                        ')';
        }

        if ( $this->update($query) === false ) {
            throw new GitDaoException( $GLOBALS['Language']->getText('plugin_git', 'dao_update_error').' : '.$this->da->isError());
        }
        if ( $insert ) {
            return $this->da->lastInsertId();
        }
        return true;
    }

    public function delete($repository) {
        $id        = $repository->getId();
        $projectId = $repository->getProjectId();
        $id        = $this->da->escapeInt($id);
        $projectId = $this->da->escapeInt($projectId);
        if ( empty($id) || empty($projectId) ) {
            throw new GitDaoException( $GLOBALS['Language']->getText('plugin_git', 'dao_delete_params') );
        }
        $deletionDate = $repository->getDeletionDate();        
        $projectName  = $repository->getProject()->getUnixName();
        $name         = $repository->getName();
        $name         .= '_'.strtotime($deletionDate);
        $name         = $projectName.'_'.$name;
        $name         = $this->da->quoteSmart($name);
        $deletionDate = $this->da->quoteSmart($deletionDate);
        $query        = ' UPDATE '.$this->getTable().' SET '.self::REPOSITORY_DELETION_DATE.'='.$deletionDate.', '.self::REPOSITORY_NAME.'='.$name.
                        ' WHERE '.self::REPOSITORY_ID.'='.$id.' AND '.self::FK_PROJECT_ID.'='.$projectId;
        $r  = $this->update($query);
        $ar = $this->da->affectedRows();
        if ( $r === false || $ar == 0 ) {
            throw new GitDaoException($GLOBALS['Language']->getText('plugin_git', 'dao_delete_error').' '.$this->da->isError());
        }
        if ( $ar == 1 ) {
            return true;
        }
        return false;
    }

    public function renameProject(Project $project, $newName) {
        $oldPath = $this->da->quoteSmart($project->getUnixName().'/');
        $newPath = $this->da->quoteSmart($newName.'/');
        $sql = 'UPDATE '.$this->getTable().
               ' SET '.self::REPOSITORY_PATH.' = REPLACE ('.self::REPOSITORY_PATH.','.$oldPath.','.$newPath.') '.
               ' WHERE '.self::FK_PROJECT_ID.' = '.$this->da->escapeInt($project->getId());
        return $this->update($sql);
    }

    /**
     * Obtain project's list of git repositories. May be filtered out by user to get only her own repositories
     *
     * @param Integre $projectId    Project id
     * @param Boolean $onlyGitShell If true list will contain only git repositories no gitolite
     * @param Boolean $scope        Allows to get all projects ignoring if the scope is project or personal
     * @param Integer $userId       User id
     *
     * @TODO: Add a way to obtain all project repositories including both project scope & user scope
     *
     * @return Array
     */
    public function getProjectRepositoryList($projectId, $onlyGitShell = false, $scope = true, $userId = null) {
        $condition = "";
        if ($onlyGitShell) {
            $condition .= " AND ". self::REPOSITORY_BACKEND_TYPE ." = '". self::BACKEND_GITSHELL ."' ";
        }
        $projectId = $this->da->escapeInt($projectId);
        $userId    = $this->da->escapeInt($userId);

        if (empty($projectId)) {
            return array();
        }
        if ($scope) {
            if (empty($userId)) {
                $condition .= " AND repository_scope = '".GitRepository::REPO_SCOPE_PROJECT."' ";
            } else {
                $condition .= " AND repository_creation_user_id = $userId AND repository_scope = '".GitRepository::REPO_SCOPE_INDIVIDUAL."' ";
            }
        }

        $sql = "SELECT * FROM $this->tableName
                WHERE ". self::FK_PROJECT_ID ." = $projectId
                  AND ". self::REPOSITORY_DELETION_DATE ." = '0000-00-00 00:00:00'
                  $condition
                ORDER BY CONCAT(". self::REPOSITORY_NAMESPACE .', '. self::REPOSITORY_NAME .')';
                  
        $rs = $this->retrieve($sql);
        $list = array();
        if ($rs && $rs->rowCount() > 0 ) {
            foreach ($rs as $row) {
                $repoId        = $row[self::REPOSITORY_ID];
                $list[$repoId] = $row;
            }
        }
        return $list;
    }
    
    /**
     * 
     * @return DataAccessResult
     */
    public function getActiveRepositoryPathsWithRemoteServersForAllProjects() {
        $sql = "SELECT * FROM $this->tableName
                WHERE remote_server_id IS NOT NULL
                AND repository_deletion_date = '0000-00-00 00:00:00'";
        return $this->retrieve($sql);
    }

    /**
     * Return the list of users that owns repositories in the project $projectId
     *
     * @return DataAccessResult
     */
    public function getProjectRepositoriesOwners($projectId) {
        $projectId = $this->da->escapeInt($projectId);
        $sql = "SELECT DISTINCT repository_creation_user_id, user_name, realname
                FROM $this->tableName
                    INNER JOIN user ON user.user_id = repository_creation_user_id
                WHERE ". self::FK_PROJECT_ID ." = $projectId
                  AND ". self::REPOSITORY_DELETION_DATE ." = '0000-00-00 00:00:00'
                  AND ". self::REPOSITORY_SCOPE." = 'I'
                ORDER BY user.user_name";
        return $this->retrieve($sql);
    }

    public function getAllGitoliteRespositories($projectId) {
        $projectId     = $this->da->escapeInt($projectId);
        $type_gitolite = $this->da->quoteSmart(self::BACKEND_GITOLITE);
        
        $sql = "SELECT * FROM $this->tableName
                WHERE ". self::FK_PROJECT_ID ." = $projectId
                  AND ". self::REPOSITORY_DELETION_DATE ." = '0000-00-00 00:00:00'
                  AND ". self::REPOSITORY_BACKEND_TYPE ." = $type_gitolite";
        return $this->retrieve($sql);
    }
    
    /**
     * This function initialize a GitRepository object with its database value
     * @param GitRepository $repository
     * @return <type>
     */
    public function getProjectRepository($repository) {        
        $projectId      = $repository->getProjectId();
        $repositoryPath = $repository->getPathWithoutLazyLoading();
        if ( empty($projectId) || empty($repositoryPath)  )  {
            throw new GitDaoException( $GLOBALS['Language']->getText('plugin_git', 'dao_search_params') );
        }
        $rs = $this->searchProjectRepositoryByPath($projectId, $repositoryPath);
        if ( empty($rs) ) {
            throw new GitDaoException($GLOBALS['Language']->getText('plugin_git', 'dao_search_error'));
            return false;
        }
        $result         = $rs->getRow();        
        if ( empty($result) ) {
            throw new GitDaoException($GLOBALS['Language']->getText('plugin_git', 'dao_search_error'));
            return false;
        }
        $this->hydrateRepositoryObject($repository, $result);
        return true;
    }
    
    
    public function searchProjectRepositoryByPath($projectId, $repositoryPath) {
        $projectId      = $this->da->escapeInt($projectId);
        $repositoryPath = $this->da->quoteSmart($repositoryPath);
        $query = 'SELECT * '.
                 ' FROM '.$this->getTable().
                 ' WHERE '.self::REPOSITORY_PATH.'='.$repositoryPath.
                 ' AND   '.self::FK_PROJECT_ID.'='.$projectId.
                 ' AND   '.self::REPOSITORY_DELETION_DATE.'='."'0000-00-00 00:00:00'";
        return $this->retrieve($query);
    }

    public function hasChild($repository) {
        $repoId = $this->da->escapeInt( $repository->getId() );
        if ( empty($repoId) ) {
            throw new GitDaoException( $GLOBALS['Language']->getText('plugin_git', 'dao_child_params') );
        }
        $query = 'SELECT '.self::REPOSITORY_ID.
                 ' FROM '.$this->getTable().
                 ' WHERE '.self::REPOSITORY_PARENT.'='.$repoId.' AND '.self::REPOSITORY_DELETION_DATE.'='."'0000-00-00 00:00:00'";
        $rs = $this->retrieve($query);
        if ( empty($rs) ) {
            return false;
        }
        $count = $rs->rowCount();
        if ( empty($count) ) {
            return false;
        }
        return true;
    }

    /**
     * This function log a Git Push in the database
     *
     * @param Integer $repoId        Id of the git repository
     * @param Integer $UserId        Id of the user that performed the push
     * @param Integer $pushTimestamp Date of the push
     * @param Integer $commitsNumber Number of commits
     *
     * @return Boolean
     */
    public function logGitPush($repoId, $userId, $pushTimestamp, $commitsNumber) {
        $repositoryId  = $this->da->escapeInt($repoId);
        $userId        = $this->da->escapeInt($userId);
        $commitsNumber = $this->da->escapeInt($commitsNumber);
        $pushDate      = $this->da->escapeInt($pushTimestamp);
        $query         = "INSERT INTO plugin_git_log (".self::REPOSITORY_ID.",
                                                      user_id,
                                                      push_date,
                                                      commits_number
                                                      ) values (
                                                      $repositoryId,
                                                      $userId,
                                                      $pushDate,
                                                      $commitsNumber
                                                      )";
        return $this->update($query);
    }

    public function getProjectRepositoryById($repository) {
        $id = (int)$repository->getId();
        if ( empty($id) ) {
            return false;
        }
        $rs = $this->searchProjectRepositoryById($id);
        if ( empty($rs) ) {
            throw new GitDaoException($GLOBALS['Language']->getText('plugin_git', 'dao_search_error'));
            return false;
        }
        $result = $rs->getRow();
        if ( empty($result) ) {
            throw new GitDaoException($GLOBALS['Language']->getText('plugin_git', 'dao_search_error'));
            return false;
        }
        $this->hydrateRepositoryObject($repository, $result);
        return true;
    }
    
    /**
     * @param Intger $id
     * 
     * @return DataAccessResult
     */
    public function searchProjectRepositoryById($id) {
        $id = $this->da->escapeInt($id);
        $query = 'SELECT * '.
                 ' FROM '.$this->getTable().
                 ' WHERE '.self::REPOSITORY_ID.'='.$id.
                 ' AND '.self::REPOSITORY_DELETION_DATE." = '0000-00-00 00:00:00'";
        return $this->retrieve($query);
    }

    public function searchDeletedRepositoryById($id) {
        $id = $this->da->escapeInt($id);
        $query = 'SELECT * '.
                 ' FROM '.$this->getTable().
                 ' WHERE '.self::REPOSITORY_ID.'='.$id.
                 ' AND '.self::REPOSITORY_DELETION_DATE." != '0000-00-00 00:00:00'";
        return $this->retrieve($query);
    }
    
    /**
     * Retrieve Git repository data given its name and its group name.
     *
     * @param String $repositoryName Name of the repository we are looking for.
     * @param String $projectId      ID of the project to which the repository belong.
     *
     * @return DataAccessResult
     */
    public function getProjectRepositoryByName($repositoryName, $projectId) {
        $projectId = $this->da->escapeInt($projectId);
        $repositoryName = $this->da->quoteSmart($repositoryName);
        $query = 'SELECT * '.' FROM '.$this->getTable().
                 ' WHERE '.self::REPOSITORY_NAME.'='.$repositoryName.' AND '.self::FK_PROJECT_ID.'='.$projectId.' AND '.self::REPOSITORY_DELETION_DATE.'='."'0000-00-00 00:00:00'";
        return $this->retrieve($query);
    }

    public function hydrateRepositoryObject(GitRepository $repository, $result) {
        $repository->setName($result[self::REPOSITORY_NAME]);
        $repository->setPath($result[self::REPOSITORY_PATH]);
        $repository->setId($result[self::REPOSITORY_ID]);
        $repository->setDescription($result[self::REPOSITORY_DESCRIPTION]);
        $repository->setParentId($result[self::REPOSITORY_PARENT]);
        $project = ProjectManager::instance()->getProject($result[self::FK_PROJECT_ID]);
        $repository->setProject($project);
        $repository->setCreationDate($result[self::REPOSITORY_CREATION_DATE]);        
        $user    = UserManager::instance()->getUserById($result[self::REPOSITORY_CREATION_USER_ID]);
        $repository->setCreator($user);
        $repository->setIsInitialized($result[self::REPOSITORY_IS_INITIALIZED]);
        $repository->setDeletionDate($result[self::REPOSITORY_DELETION_DATE]);
        $repository->setAccess($result[self::REPOSITORY_ACCESS]);
        $repository->setMailPrefix($result[self::REPOSITORY_MAIL_PREFIX]);
        $repository->setBackendType($result[self::REPOSITORY_BACKEND_TYPE]);
        $repository->setNamespace($result[self::REPOSITORY_NAMESPACE]);
        $repository->setScope($result[self::REPOSITORY_SCOPE]);
        $repository->setRemoteServerId($result[self::REMOTE_SERVER_ID]);
        $repository->loadNotifiedMails();
    }

    /**
     * Instanciate a new GitRepository object based on row entry
     * @param array $row
     * @return GitRepository
     */
    public function instanciateFromRow($row) {
        $repository = new GitRepository();
        $this->hydrateRepositoryObject($repository, $row);
        return $repository;
    }

    /**
     * Count number of repositories grouped by backend type
     *
     * @param String  $startDate   Start date
     * @param String  $endDate     End date
     * @param Integer $projectId   Project Id
     * @param Boolean $stillActive Select only reposirtories that still active
     *
     * @return DataAccessResult
     */
    public function getBackendStatistics($backend, $startDate, $endDate, $projectId = null, $stillActive = false) {
        $condition = '';
        if ($projectId) {
            $condition = "AND ".self::FK_PROJECT_ID."=".$this->da->escapeInt($projectId);
        }
        if ($stillActive) {
            $condition .= " AND status = 'A' AND ".self::REPOSITORY_DELETION_DATE."="."'0000-00-00 00:00:00' ";
        }
        $query = "SELECT count(repository_id) AS count,
                  YEAR(repository_creation_date) AS year,
                  MONTHNAME(STR_TO_DATE(MONTH(repository_creation_date), '%m')) AS month
                  FROM ".$this->getTable()."
                  JOIN groups g ON group_id = project_id
                  WHERE repository_backend_type = ".$this->da->quoteSmart($backend)."
                    AND repository_creation_date BETWEEN CAST(".$this->da->quoteSmart($startDate)." AS DATETIME) AND CAST(".$this->da->quoteSmart($endDate)." AS DATETIME)
                    ".$condition."
                  GROUP BY year, month
                  ORDER BY year, STR_TO_DATE(month,'%M')";
        return $this->retrieve($query);
    }

    public function isRepositoryExisting($project_id, $path) {
        $project_id = $this->da->escapeInt($project_id);
        $path       = $this->da->quoteSmart($path);
        $sql = "SELECT NULL
                FROM plugin_git
                WHERE repository_path = $path
                  AND project_id = $project_id
                  AND ".self::REPOSITORY_DELETION_DATE." = '0000-00-00 00:00:00'
                LIMIT 1";
        return count($this->retrieve($sql)) > 0;
    }
    
    /**
     * 
     * @param int $repository_id
     * @param int $remote_server_id
     * 
     * @return Boolean
     */
    public function switchToGerrit($repository_id, $remote_server_id) {
        $repository_id    = $this->da->escapeInt($repository_id);
        $remote_server_id = $this->da->escapeInt($remote_server_id);
        $sql = "UPDATE plugin_git
                SET remote_server_id = $remote_server_id
                WHERE repository_id = $repository_id";
        return $this->update($sql);
    }
    
    /**
     * @return bool
     */
    public function isRemoteServerUsed($remote_server_id) {
        $remote_server_id = $this->da->escapeInt($remote_server_id);
        $sql = "SELECT NULL
                FROM plugin_git
                WHERE remote_server_id = $remote_server_id
                LIMIT 1";
        return count($this->retrieve($sql)) > 0;
    }

    public function searchGerritRepositoriesWithPermissionsForUGroup($project_id, $ugroup_id) {
        $project_id = $this->da->escapeInt($project_id);
        $ugroup_id  = $this->da->escapeInt($ugroup_id);
        $sql = "SELECT * FROM plugin_git git
                    JOIN permissions ON (permissions.object_id = CAST(git.repository_id as CHAR) AND permissions.permission_type LIKE 'PLUGIN_GIT%')
                WHERE git.remote_server_id IS NOT NULL
                    AND permissions.ugroup_id = $ugroup_id
                    AND git.project_id = $project_id";
        return $this->retrieve($sql);

    }
}

?>
