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


require_once('GitBackend.class.php');
require_once('GitDriver.class.php');
require_once('GitDao.class.php');
require_once(dirname(__FILE__).'/../DVCS/DVCSRepository.class.php');
require_once('exceptions/GitRepositoryException.class.php');
/**
 * Description of GitRepositoryclass
 *
 * @author Guillaume Storchi
 */

class GitRepository implements DVCSRepository {

      
    const REPO_EXT       = '.git';
   
    const PRIVATE_ACCESS       = 'private';
    const PUBLIC_ACCESS        = 'public';
    
    private $id;
    private $parentId;
    private $name;
    private $path;
    private $rootPath;
    
    private $project;    

    private $description;
    private $isInitialized;
    private $creationDate;
    private $creator;
    private $deletionDate;
    private $access;
    private $mailPrefix;
    private $notifiedMails;

    private $hooks;
    private $branches;
    private $config;
    
    private $parent;    
    private $loaded;    
    private $dao;

    public function __construct() {

        $this->hash        = '';        
        $this->rootPath    = '';
        $this->path        = '';        

        $this->name           = '';
        $this->description    = '';
        $this->creationDate   = '';
        $this->creator        = null;
        $this->deletionDate   = '';
        $this->isInitialized  = 0;
        $this->access         = 'private';
        $this->mailPrefix     = '[SCM]';
        $this->notifiedMails = array();

        $this->hooks       = array();
        $this->branches    = array();

        $this->config      = array();
        $this->parent      = null;
        $this->parentId    = 0;
        $this->loaded      = false;        
    }       

    /**
     * Wrapper
     * @return Boolean
     */
    public function exists() {
        try {
            $this->load();
        } catch(Exception $e) {
            return false;
        }
        return true;
    }
    /**
     * Loads data from database
     */
    public function load($force=false) {
        //already loaded
        if ( $force === false && $this->loaded === true ) {
            return true;
        }
        $id = $this->getId();
        if ( empty($id) ) {            
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
    public function save() {
        $this->getBackend()->save($this);
    }

    /**
     * Allow to mock in UT
     * @return GitDao
     */
    public function getDao() {
        if ( empty($this->dao) ) {
            $this->dao = new GitDao();
        }
        return $this->dao;
    }

    /**
     * Define Backend used by repo
     *
     * @param $backend
     */
    public function setBackendType($backendType) {
        $this->backendType = $backendType;
    }

    protected function getBackendType() {
        return $this->backendType;
    }
    
    /**
     * Define Backend used by repo
     * 
     * @param $backend
     */
    public function setBackend($backend) {
        $this->backend = $backend;
    }

    /**
     * Allow to mock in UT
     *
     * @return Git_Backend_Interface
     */
    public function getBackend() {
        if ( empty($this->backend) ) {
            switch ($this->getBackendType()) {
                case GitDao::BACKEND_GITOLITE:
                    $this->backend = new Git_Backend_Gitolite(new Git_GitoliteDriver());
                    break;
                default:
                    $this->backend = Backend::instance('Git','GitBackend');
            }
        }
        return $this->backend;
    }
    
    public function getPostReceiveMailManager() {
        return new Git_PostReceiveMailManager();
    }
    
    public function setId($id) {
        $this->id = $id;
    }

    public function getId() {
        return $this->id;
    }
    
    public function hasChild() {
        $this->load();
        return $this->getDao()->hasChild($this);
    }

    /**
     * Shortcut of setParent
     * @param Integer $id
     */
    public function setParentId($id) {
        $this->parentId = $id;
    }

    /**
     * Shortcut
     * @return Integer
     */
    public function getParentId() {
        return $this->parentId;
    }

    /**     
     * @param GitRepository $parentRepository
     */
    public function setParent($parentRepository) {
        $this->parent   = $parentRepository;       
    }

    /**
     * Gives the parent GitRepository object of this
     * Look into the database
     * @return GitRepository
     */
    public function getParent() {
        if ( empty($this->parent) ) {            
            $this->load();            
            $parent = new GitRepository();
            $parent->setId($this->parentId);
            if ( !$this->getDao()->getProjectRepositoryById($parent) ) {
                //no parent or error
                $parent = null;
            } else {
                //there is a parent
                $this->parentId = $parent->getId();//not very useful
            }
            $this->parent = $parent;
        }
        return $this->parent;
    }

    /**
     * @param Project $project
     */
    public function setProject($project) {
        $this->project = $project;
    }

    /**
     * @return Project
     */
    public function getProject() {
        return $this->project;
    }      

    public function getProjectId() {
        $project = $this->getProject();
        if ( empty($project) ) {
            return false;
        }
        return $this->getProject()->getId();
    }
    
    /**
     * @param String $name
     */
    public function setName($name) {
        $this->name = $name;
    }

    /**
     * @return String;
     */
    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }    

    public function setIsInitialized($initialized) {
        $this->isInitialized = $initialized;
    }

    public function getIsInitialized() {
        return $this->isInitialized;
    }
    /**
     *  Check repo status, if it is not initialized
     * @return <type>
     */
    public function isInitialized() {
        $this->load();
        if ( $this->isInitialized == 1 ) {
            return true;
        }        
        else {
            if ( $this->getBackend()->isInitialized($this) === true ) {
                $this->isInitialized = 1;               
                return true;
            }
            else {
                return false;
            }
        }
    }    

    public function setCreationDate($date) {
        $this->creationDate = $date;
    }

    public function getCreationDate() {
        return $this->creationDate;
    }

    public function setCreator($user) {
        $this->creator = $user;
    }

    public function getCreator() {
        return $this->creator;
    }

    public function getCreatorId() {
        if ( !empty($this->creator) ) {
            return $this->creator->getId();
        }
        return 0;
    }

    public function setDeletionDate($date) {        
        $this->deletionDate = $date;
    }

    public function getDeletionDate() {
        if ( empty($this->deletionDate) ) {
            $this->deletionDate = date('Y-m-d H:i:s');
        }
        return $this->deletionDate;
    }
    /**
     * relative path to the repository dir (actually this is the project directory)
     * @param String $dir
     */
    public function setRootPath($dir) {
        $this->rootPath = $dir;
    }

    /*
     * Gives the root path which is the project directory
     * @return String
     */
    public function getRootPath() {
       if ( !$this->exists() ) {
           $this->rootPath = $this->project->getUnixName();
       }
       return $this->rootPath;
    }

    /**
     * @param String $path
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * Gives the full relative path (from git root directory) to the repository
     * @return String
     */
    public function getPath() {
        if ( empty($this->path) ) {
            $rootPath   = $this->getRootPath();
            $name       = $this->getName();
            //can not return a bad path
            if ( empty($rootPath) || empty($name) ) {
                $this->path = '';
            } else {
                $this->path = $rootPath.DIRECTORY_SEPARATOR.$name.self::REPO_EXT;
            }
        }
        return $this->path;
    }

    /**
     * Return path on the filesystem where the repositories are stored.
     *
     * @return String
     */
    public function getGitRootPath() {
        return $this->getBackend()->getGitRootPath();
    }

    public function getAccess() {
        return $this->access;
    }

    public function setAccess($access) {
        if ( $access != self::PRIVATE_ACCESS && $access != self::PUBLIC_ACCESS ) {
            throw new GitRepositoryException('Unknown repository access value ');
        }
        $this->access = $access;
    }    

    public function changeAccess() {
        $this->getBackend()->changeRepositoryAccess($this);
    }

    public function isPublic() {
        if ( $this->access == self::PUBLIC_ACCESS ) {
            return true;
        }
        return false;
    }

    public function isPrivate() {
        if ( $this->access == self::PRIVATE_ACCESS ) {
            return true;
        }
        return false;
    }

    public function getMailPrefix() {
        return $this->mailPrefix;
    }

    public function setMailPrefix($mailPrefix) {
        $this->mailPrefix = $mailPrefix;
    }

    public function changeMailPrefix() {
        $this->getBackend()->changeRepositoryMailPrefix($this);
    }

    public function setNotifiedMails() {
        $postRecMailManager = $this->getPostReceiveMailManager();
        $this->notifiedMails = $postRecMailManager->getNotificationMailsByRepositoryId($this->getId());
    }

    public function getNotifiedMails() {
        return $this->notifiedMails;
    }

    public function getAccessURL() {
        return $this->getBackend()->getAccessURL($this);
    }

    /**
     * Clone a repository, it inherits access
     * @param String forkName
     */
    public function fork($forkName) {        
        $clone = new GitRepository();
        $clone->setName($forkName);
        $clone->setProject( $this->getProject() );
        $clone->setParent( $this );               
        $clone->setCreationDate( date('Y-m-d H:i:s') );
        $clone->setCreator( $this->getCreator() );
        $clone->setAccess( $this->getAccess() );
        $clone->setIsInitialized(1);
        $clone->setDescription('-- Default description --');
        $this->getBackend()->createFork($clone);
    }

    /**
     * Create a reference repository
     */
    public function create() {        
        $this->getBackend()->createReference($this);
    }

    /**
     * Delete a repository (reference and fork)
     * @todo think about a really good way to check repository path deletion!!!
     */
    public function delete() {
        $project = $this->getProject();
        //if empty project name -> get out of here
        if ( !empty($project) ) {
            if (  $project->getUnixName() == '' ) {
                return false;
            }
        } else {
            return false;
        }
        //if empty name -> get out of here
        $name  = $this->getName();
        if ( empty($name) ) {
            return false;
        }        
        $date  = $this->getDeletionDate();
        if ( empty($date) || $date == '0000-00-00 00:00:00') {
            $this->setDeletionDate( date('Y-m-d H:i:s') );
        }

        //remove notification from DB
        $postRecMailManager = $this->getPostReceiveMailManager();
        $postRecMailManager->removeMailByRepository($this);

        $this->getBackend()->delete($this);
    }

    /**
     * Rename project
     */
    public function renameProject(Project $project, $newName) {
        $newName = strtolower($newName);
        if ($this->getBackend()->renameProject($project, $newName)) {
            return $this->getDao()->renameProject($project, $newName);
        }
        return false;
    }

    /**
     * Verify if the notfication is alreadyu enabled for the given mail
     * 
     * @param String $mail
     * @return Boolean
     */
    public function isAlreadyNotified ($mail) {
        return (in_array($mail, $this->getNotifiedMails())) ;
    }
    /**
     * Add the @mail to the config git section and to DB
     * 
     * @param String $mail
     * 
     * @return Boolean
     */
    public function notificationAddMail($mail) {
            $this->notifiedMails[] = $mail;
            $postRecMailManager = $this->getPostReceiveMailManager();
            if ($postRecMailManager->addMail($this->getId(), $mail)) {
                return $this->getBackend()->changeRepositoryMailingList($this);
            }
            return false;
    }

    /**
     * Remove the @mail from the config git section and from DB
     * @param String $mail
     * 
     * @return Boolean
     */
    public function notificationRemoveMail($mail) {
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
    public function getNonMemberMails() {
        $mails = $this->getNotifiedMails();
        $mailsToDelete = array();
        $um = UserManager::instance();
        foreach ($mails as $mail) {
            try {
                $user = $um->getUserByEmail($mail);
                if (!$user || !$user->isMember($this->getProjectId())) {
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
     * @param User $user The user to test
     *
     * @return Boolean
     */
    public function userCanRead($user) {
        return $this->getBackend()->userCanRead($user, $this);
    }


    /**
     * Test if user can modify repository configuration
     *
     * @param User $user The user to test
     *
     * @return Boolean
     */
    public function userCanAdmin($user) {
        return $user->isMember($this->getProjectId(), 'A');
    }
    
    /**
     * Validate the name for a repository
     *
     * @param string $name The name to validate
     *
     * @return bool true if valid, false otherwise
     */
    public function isNameValid($name) {
        $len = strlen($name);
        return 1 <= $len && $len < GitDao::REPO_NAME_MAX_LENGTH &&
               !preg_match('`[^'. $this->getBackend()->getAllowedCharsInNamePattern() .']`', $name) &&
               !preg_match('`(?:^|/)\.`', $name) && //do not allow dot at the begining of a world
               !preg_match('`\.\.`', $name); //do not allow double dots (prevent path collisions)
    }
}

?>
