<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

use Tuleap\Git\PathJoinUtil;

class GitRepository
{
    public const REPO_EXT = '.git';

    public const PRIVATE_ACCESS = 'private';
    public const PUBLIC_ACCESS  = 'public';

    public const DEFAULT_MAIL_PREFIX   = '[SCM]';
    public const REPO_SCOPE_PROJECT    = 'P';
    public const REPO_SCOPE_INDIVIDUAL = 'I';
    public const DEFAULT_DESCRIPTION   = '-- Default description --';

    private $id;
    private $parentId;
    private $name;
    private $path;
    private $rootPath;

    private $project;

    private $description;
    private $isInitialized;
    private $creationDate;
    private ?PFUser $creator = null;
    private $deletionDate;
    private $access;
    private $mailPrefix;
    private $notifiedMails;

    private $parent;
    private $loaded;
    private $dao;
    private $namespace;
    private $backup_path;
    private $scope;
    private $remote_server_id;
    private $remote_server_disconnect_date;
    private $remote_project_deletion_date;
    private $remote_project_is_deleted;
    private $remote_server_migration_status;
    private $last_push_date;

    protected $backendType;
    private ?Git_Backend_Interface $backend = null;

    public function __construct()
    {
        $this->rootPath = '';
        $this->path     = '';

        $this->name          = '';
        $this->description   = '';
        $this->creationDate  = '';
        $this->deletionDate  = '';
        $this->isInitialized = 0;
        $this->access        = 'private';
        $this->mailPrefix    = self::DEFAULT_MAIL_PREFIX;
        $this->notifiedMails;
        $this->parent   = null;
        $this->parentId = 0;
        $this->loaded   = false;
        $this->scope    = self::REPO_SCOPE_PROJECT;
    }

    /**
     * Wrapper for tests
     *
     * @return UserManager
     */
    public function _getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * Wrapper for tests
     *
     * @return ProjectManager
     */
    public function _getProjectManager()
    {
        return ProjectManager::instance();
    }

    /**
     * WARNING: this method will attempt to "Lazy load" the current object
     *          do not use it or kitten will die.
     *
     * @deprecated
     *
     * @return bool
     */
    public function exists()
    {
        try {
            $this->load();
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Loads data from database. Use GitRepositoryFactory instead.
     *
     * WARNING: this method will attempt to "Lazy load" the current object
     *          do not use it or kitten will die.
     *
     * @see GitRepositoryFactory
     *
     * @deprecated
     *
     */
    public function load($force = false)
    {
        //already loaded
        if ($force === false && $this->loaded === true) {
            return true;
        }
        $id = $this->getId();
        if (empty($id)) {
            $this->loaded = $this->getDao()->getProjectRepository($this);
        } else {
            $this->loaded = $this->getDao()->getProjectRepositoryById($this);
        }
        //loading failed
        return $this->loaded;
    }

    /**
     * Save current GitRepostiroy object to the database
     */
    public function save()
    {
        $this->getBackend()->save($this);
    }

    /**
     * Allow to mock in UT
     * @return GitDao
     */
    public function getDao()
    {
        if (empty($this->dao)) {
            $this->dao = new GitDao();
        }
        return $this->dao;
    }

    /**
     * Define Backend used by repo
     *
     * @param $backend
     */
    public function setBackendType($backendType)
    {
        $this->backendType = $backendType;
    }

    /** @return string */
    public function getBackendType()
    {
        return $this->backendType;
    }

    /**
     * Define Backend used by repo
     *
     * @param $backend
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * Allow to mock in UT
     *
     * @return Git_Backend_Interface
     */
    public function getBackend()
    {
        if (empty($this->backend)) {
            $git_plugin = PluginManager::instance()->getPluginByName('git');
            \assert($git_plugin instanceof GitPlugin);
            $backend_type  = $this->getBackendType();
            $this->backend = match ($backend_type) {
                GitDao::BACKEND_GITOLITE => $git_plugin->getBackendGitolite(),
                default => throw new \Tuleap\Git\UnsupportedBackendTypeException($backend_type),
            };
        }
        return $this->backend;
    }

    public function getPostReceiveMailManager()
    {
        return new Git_PostReceiveMailManager();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function hasChild()
    {
        $this->load();
        return $this->getDao()->hasChild($this);
    }

    /**
     * Shortcut of setParent
     * @param int $id
     */
    public function setParentId($id)
    {
        $this->parentId = $id;
    }

    /**
     * Shortcut
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @param GitRepository $parentRepository
     */
    public function setParent($parentRepository)
    {
        $this->parent = $parentRepository;
    }

    /**
     * Gives the parent GitRepository object of this
     * Look into the database
     * @return GitRepository|null
     */
    public function getParent()
    {
        if (empty($this->parent)) {
            $factory      = new GitRepositoryFactory($this->getDao(), $this->_getProjectManager());
            $this->parent = $factory->getRepositoryById($this->getParentId());
        }
        return $this->parent;
    }

    /**
     * @param Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    public function getProjectId()
    {
        $project = $this->getProject();
        if (empty($project)) {
            return false;
        }
        return $this->getProject()->getId();
    }

    public function belongsToProject(Project $project)
    {
        return $this->project->getId() == $project->getID();
    }

    /**
     * Retrieve Git repository ID knowing the repository name and its group name.
     *
     * @param String $repositoryName Name of the repository
     * @param String $projectName    Name of the project
     *
     * @return int
     */
    public function getRepositoryIDByName($repositoryName, $projectName)
    {
        $pm      = $this->_getProjectManager();
        $project = $pm->getProjectByUnixName($projectName);
        $repoId  = 0;
        if ($project) {
            $projectId = $project->getID();
            $row       = $this->getDao()->getProjectRepositoryByName($repositoryName, $projectId);
            if ($row && ! empty($row)) {
                     $repoId = $row[GitDao::REPOSITORY_ID];
            }
        }
        return $repoId;
    }

    /**
     * @param String $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Return repository name. Consider using getFullName instead
     *
     * @see GitRepository::getFullName
     *
     * @return String
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return repository namespace. Consider using getFullName instead
     *
     * @see GitRepository::getFullName
     *
     * @return String
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
    }

    /**
     * Return relative path from project repository root (without .git)
     *
     * @return string
     */
    public function getFullName()
    {
        return PathJoinUtil::unixPathJoin([$this->getNamespace(), $this->getName()]);
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setIsInitialized($initialized)
    {
        $this->isInitialized = $initialized;
    }

    public function getIsInitialized()
    {
        return $this->isInitialized;
    }

    /**
     *  Check repo status, if it is not initialized
     * @return bool
     */
    public function isInitialized()
    {
        $this->load();
        if ($this->isInitialized == 1) {
            return true;
        } else {
            if ($this->getBackend()->isInitialized($this) === true) {
                $this->isInitialized = 1;
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     *
     * @return bool
     */
    public function isCreated()
    {
        return $this->getBackend()->isCreated($this);
    }

    public function setCreationDate($date)
    {
        $this->creationDate = $date;
    }

    public function getCreationDate()
    {
        return $this->creationDate;
    }

    public function setCreator(?PFUser $user): void
    {
        $this->creator = $user;
    }

    public function getCreator(): PFUser
    {
        return $this->creator ?? $this->_getUserManager()->getUserAnonymous();
    }

    public function getCreatorId()
    {
        return $this->getCreator()->getId();
    }

    public function setDeletionDate($date)
    {
        $this->deletionDate = $date;
    }

    public function getDeletionDate()
    {
        if (empty($this->deletionDate)) {
            $this->deletionDate = date('Y-m-d H:i:s');
        }
        return $this->deletionDate;
    }

    /**
     * relative path to the repository dir (actually this is the project directory)
     * @param String $dir
     */
    public function setRootPath($dir)
    {
        $this->rootPath = $dir;
    }

    /**
     * Gives the root path which is the project directory
     *
     * WARNING: this method will attempt to "Lazy load" the current object
     *          do not use it or kitten will die.
     *
     * @deprecated
     *
     * @return String
     */
    public function getRootPath()
    {
        if (! $this->exists()) {
            $this->rootPath = $this->project->getUnixName();
        }
        return $this->rootPath;
    }

    /**
     * @param String $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Gives the scope of the repository
     * @return String
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param String $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }

    /**
     * Gives the full relative path (from git root directory) to the repository. Consider using getFullName instead.
     *
     * @see GitRepository::getFullName
     *
     * @return String
     */
    public function getPath()
    {
        if (empty($this->path)) {
            $rootPath = $this->getRootPath();
            $name     = $this->getName();
            //can not return a bad path
            if (empty($rootPath) || empty($name)) {
                $this->path = '';
            } else {
                $this->path = self::getPathFromProjectAndName($this->project, $name);
            }
        }
        return $this->path;
    }

    /**
     * Gives the full relative path (from git root directory) to the repository.
     *
     * Countrary of self::getPath, this method will not attempt to load the
     * current object from the database if object is not already built from the DB.
     * It's especially useful on repository creation.
     *
     * @return String
     */
    public function getPathWithoutLazyLoading()
    {
        if (! $this->path) {
            $this->path = self::getPathFromProjectAndName($this->getProject(), $this->getName());
        }
        return $this->path;
    }

    public static function getPathFromProjectAndName(Project $project, $name)
    {
        return $project->getUnixName() . DIRECTORY_SEPARATOR . $name . self::REPO_EXT;
    }

    /**
     * Return the full absolute path to the repository
     *
     * @return String
     */
    public function getFullPath()
    {
        $root_path = $this->getGitRootPath();
        if (is_string($root_path) && strlen($root_path) > 0) {
            $root_path = ($root_path[strlen($root_path) - 1] === DIRECTORY_SEPARATOR) ? $root_path : $root_path . DIRECTORY_SEPARATOR;
        }

        return $root_path . $this->getPathWithoutLazyLoading();
    }

    /**
     * Return path on the filesystem where the repositories are stored.
     *
     * @return String
     */
    public function getGitRootPath()
    {
        return $this->getBackend()->getGitRootPath();
    }

    public function getAccess()
    {
        return $this->access;
    }

    public function setAccess($access)
    {
        if ($access != self::PRIVATE_ACCESS && $access != self::PUBLIC_ACCESS) {
            throw new GitRepositoryException('Unknown repository access value ');
        }
        $this->access = $access;
    }

    public function changeAccess()
    {
        $this->getBackend()->changeRepositoryAccess($this);
    }

    public function isPublic()
    {
        if ($this->access == self::PUBLIC_ACCESS) {
            return true;
        }
        return false;
    }

    public function isPrivate()
    {
        if ($this->access == self::PRIVATE_ACCESS) {
            return true;
        }
        return false;
    }

    /**
     * Returns hooks.showrev string to be used in git config
     *
     * @return String
     */
    public function getPostReceiveShowRev(Git_GitRepositoryUrlManager $url_manager)
    {
        $url = $this->getDiffLink($url_manager, '%%H');

        $format = 'format:URL:    ' . $url . '%%nAuthor: %%an <%%ae>%%nDate:   %%aD%%n%%n%%s%%n%%b';

        $showrev = 't=%s; ' .
            'git show ' .
            '--name-status ' .
            "--pretty='" . $format . "' " .
            '$t';
        return $showrev;
    }

    public function getDiffLink(Git_GitRepositoryUrlManager $url_manager, $revision_hash)
    {
        $url  = \Tuleap\ServerHostname::HTTPSUrl();
        $url .= $url_manager->getRepositoryBaseUrl($this);
        $url .= '?a=commitdiff&h=' . $revision_hash;

        return $url;
    }

    public function getMailPrefix()
    {
        return $this->mailPrefix;
    }

    public function setMailPrefix($mailPrefix)
    {
        $this->mailPrefix = $mailPrefix;
    }

    public function changeMailPrefix()
    {
        $this->getBackend()->changeRepositoryMailPrefix($this);
    }

    public function loadNotifiedMails()
    {
        $postRecMailManager  = $this->getPostReceiveMailManager();
        $this->notifiedMails = $postRecMailManager->getNotificationMailsByRepositoryId($this->getId());
    }

    public function setNotifiedMails($mails)
    {
        $this->notifiedMails = $mails;
    }

    public function getNotifiedMails()
    {
        if ($this->notifiedMails === null) {
            $this->loadNotifiedMails();
        }
        return $this->notifiedMails;
    }

    public function getAccessURL()
    {
        return $this->getBackend()->getAccessURL($this);
    }

    /**
     * Physically delete a repository already marked for deletion
     */
    public function delete()
    {
        $this->getBackend()->delete($this);
    }

    /**
     * Perform logical deletion repository in DB
     *
     * @todo: makes deletion of repo in gitolite asynchronous
     *
     * @throws GitBackendException
     */
    public function markAsDeleted()
    {
        if ($this->canBeDeleted()) {
            $this->forceMarkAsDeleted();
        } else {
            throw new GitBackendException(dgettext('tuleap-git', 'Unable to delete repository: path outside project repository root'));
        }
    }

    /**
     * Force logical deletion of repository
     */
    public function forceMarkAsDeleted()
    {
        $this->setDeletionDate(date('Y-m-d H:i:s'));

        $postRecMailManager = $this->getPostReceiveMailManager();
        $postRecMailManager->markRepositoryAsDeleted($this);

        $this->getBackend()->markAsDeleted($this);
    }

    /**
     * Verify if the notfication is alreadyu enabled for the given mail
     *
     * @param String $mail
     * @return bool
     */
    public function isAlreadyNotified($mail)
    {
        return (in_array($mail, $this->getNotifiedMails()));
    }

    /**
     * Add the @mail to the config git section and to DB
     *
     * @param String $mail
     *
     * @return bool
     */
    public function notificationAddMail($mail)
    {
        $this->notifiedMails[] = $mail;
        $postRecMailManager    = $this->getPostReceiveMailManager();
        if ($postRecMailManager->addMail($this->getId(), $mail)) {
            return $this->getBackend()->changeRepositoryMailingList($this);
        }
        return false;
    }

    /**
     * Remove the @mail from the config git section and from DB
     * @param String $mail
     *
     * @return bool
     */
    public function notificationRemoveMail($mail)
    {
        if (in_array($mail, $this->getNotifiedMails())) {
            $postRecMailManager = $this->getPostReceiveMailManager();
            return $postRecMailManager->removeMailByRepository($this, $mail);
        }
        return true;
    }

    /**
     * Get the list of mails notified without being project members
     *
     * @return Array
     */
    public function getNonMemberMails()
    {
        $mails         = $this->getNotifiedMails();
        $mailsToDelete = [];
        $um            = UserManager::instance();
        foreach ($mails as $mail) {
            try {
                $user = $um->getUserByEmail($mail);
                if (! $user || ! $user->isMember($this->getProjectId())) {
                    $mailsToDelete[] = $mail;
                }
            } catch (Exception $e) {
            }
        }
        return $mailsToDelete;
    }

    /**
     * Test is user can read the content of this repository and metadata
     *
     * @param PFUser $user The user to test
     *
     * @return bool
     */
    public function userCanRead($user)
    {
        return $this->getBackend()->userCanRead($user, $this);
    }

    /**
     * Test if user can modify repository configuration
     *
     * @param PFUser $user The user to test
     *
     * @return bool
     */
    public function userCanAdmin($user)
    {
        return $user->isMember($this->getProjectId(), 'A');
    }

    /**
     * Check if path is a subpath of referencepath
     *
     * @param String $referencePath The path the repository is supposed to belong to
     * @param String $repositoryPath The path of the repository
     *
     * @return bool
     */
    public function isSubPath($referencePath, $repositoryPath)
    {
        $repository_path = realpath($repositoryPath);
        $reference_path  = realpath($referencePath);

        return $reference_path !== false && $repository_path !== false && strpos(realpath($repository_path), realpath($reference_path)) === 0;
    }

    /**
     * Check if path contains .git at the end
     *
     * @param String $path
     *
     * @return bool
     */
    public function isDotGit($path)
    {
        return (substr($path, -4) == '.git');
    }

    /**
     * Check if repository can be deleted
     *
     * @return bool
     */
    public function canBeDeleted()
    {
        if ($this->getPath() && $this->getBackend()->canBeDeleted($this)) {
            $referencePath  = $this->getBackend()->getGitRootPath() . '/' . $this->getProject()->getUnixName();
            $repositoryPath = $this->getBackend()->getGitRootPath() . '/' . $this->getPath();
            return ($this->isSubPath($referencePath, $repositoryPath) && $this->isDotGit($repositoryPath));
        }
        return false;
    }

    /**
     * Say if a repo belongs to a user
     *
     * @param PFUser $user the user
     *
     * @return true if the repo is a personnal rep and if it is created by $user
     */
    public function belongsTo(PFUser $user)
    {
        return $this->getScope() == self::REPO_SCOPE_INDIVIDUAL && $this->getCreatorId() == $user->getId();
    }

    public function canMigrateToGerrit()
    {
        return $this->getBackendType() == GitDao::BACKEND_GITOLITE &&
               ! $this->isMigratedToGerrit();
    }

    public function setRemoteServerId($id)
    {
        $this->remote_server_id = $id;
    }

    public function getRemoteServerId()
    {
        return $this->remote_server_id;
    }

    public function isMigratedToGerrit()
    {
        return (
            $this->remote_server_id &&
            $this->remote_server_disconnect_date == false &&
            $this->remote_project_deletion_date == false);
    }

    public function getMigrationStatus()
    {
        return $this->remote_server_migration_status;
    }

    public function wasPreviouslyMigratedButNotDeleted()
    {
        return (
            $this->remote_server_id &&
            $this->remote_server_disconnect_date != false &&
            $this->remote_project_deletion_date == false &&
            ! $this->remote_project_is_deleted);
    }

    public function setRemoteServerDisconnectDate($date)
    {
        $this->remote_server_disconnect_date = $date;
    }

    public function setRemoteProjectDeletionDate($date)
    {
        $this->remote_project_deletion_date = $date;
    }

    public function setRemoteServerMigrationStatus($status)
    {
        $this->remote_server_migration_status = $status;
    }

    /**
     * @return string html <a href="/path/to/repo">repo/name</a>
     */
    public function getHTMLLink(Git_GitRepositoryUrlManager $url_manager)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $href     = $url_manager->getRepositoryBaseUrl($this);
        $label    = $this->getName();
        return '<a href="' . $purifier->purify($href) . '">' . $purifier->purify($label) . '</a>';
    }

    public function getBackupPath()
    {
        return $this->backup_path;
    }

    public function setBackupPath($path)
    {
        $this->backup_path = $path;
    }

    public function setLastPushDate($date)
    {
        $this->last_push_date = $date;
    }

    public function getLastPushDate()
    {
        return $this->last_push_date;
    }

    /**
     * @return string
     *
     * @psalm-mutation-free
     */
    public function getPathWithoutProject()
    {
        $split_path = explode('/', $this->path);
        array_shift($split_path);
        array_pop($split_path);

        return implode('/', $split_path);
    }

    /**
     * @psalm-mutation-free
     */
    public function getLabel()
    {
        return basename($this->getName());
    }

    /**
     * @return string
     */
    public function getFullHTTPUrlWithDotGit()
    {
        return \Tuleap\ServerHostname::HTTPSUrl() . $this->getRelativeHTTPUrl() . '.git';
    }

    /**
     * @return string
     */
    public function getRelativeHTTPUrl()
    {
        return GIT_BASE_URL . '/' . $this->getProject()->getUnixName() . '/' . $this->getFullName();
    }
}
