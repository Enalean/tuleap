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
require_once('mvc/PluginActions.class.php');
require_once('events/SystemEvent_GIT_REPO_CREATE.class.php');
require_once('events/SystemEvent_GIT_REPO_CLONE.class.php');
require_once('events/SystemEvent_GIT_REPO_DELETE.class.php');
require_once('events/SystemEvent_GIT_REPO_ACCESS.class.php');
require_once('common/system_event/SystemEventManager.class.php');
require_once('GitBackend.class.php');
require_once('GitRepository.class.php');
require_once('GitDao.class.php');
require_once('Git_GitoliteDriver.class.php');
require_once('Git_Backend_Gitolite.class.php');
require_once('GitRepositoryFactory.class.php');
require_once('common/layout/Layout.class.php');

/**
 * GitActions
 * @todo call Event class instead of SystemEvent
 * @author Guillaume Storchi
 */
class GitActions extends PluginActions {

    /**
     * @var SystemEventManager
     */
    protected $systemEventManager;
    
    /**
     * Constructor
     *
     * @param PluginController     $controller         The controller
     * @param GitRepositoryFactory $factory            The factory to manage repositories
     * @param SystemEventManager   $systemEventManager The system manager
     */
    public function __construct($controller, SystemEventManager $systemEventManager) {
        parent::__construct($controller);
        $this->systemEventManager = $systemEventManager;

    }

    protected function getText($key, $params = array()) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }
    
    public function process($action, $params) {
       return call_user_func_array(array($this,$action), $params);
    }
    
    public function deleteRepository( $projectId, $repositoryId ) {
        $c            = $this->getController();
        $projectId    = intval($projectId);
        $repositoryId = intval($repositoryId);
        if ( empty($projectId) || empty($repositoryId) ) {
            $c->addError( $this->getText('actions_params_error') );            
            return false;
        }       
        $repository = new GitRepository();
        $repository->setId( $repositoryId );
        $repository->load();

        if ( $repository->hasChild() ) {
            $c->addError( $this->getText('backend_delete_haschild_error') );
            $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$repositoryId.'/');
            return false;
        }
        
        if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
            try {
                $repository->delete();
                $c->addInfo( $this->getText('actions_delete_ok') );
            } catch (Exception $e) {
                $c->addError($e->getMessage());
            }
        } else {
            $this->systemEventManager->createEvent(
                'GIT_REPO_DELETE',
                $projectId.SystemEvent::PARAMETER_SEPARATOR.$repositoryId,
                SystemEvent::PRIORITY_MEDIUM
            );
            $c->addInfo( $this->getText('actions_delete_process') );
            $c->addInfo( $this->getText('actions_delete_backup').' : '.PluginManager::instance()->getPluginByName('git')->getPluginInfo()->getPropVal('git_backup_dir') );
        }
        $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
    }

    public function createReference($projectId, $repositoryName) {
        $c         = $this->getController();
        $projectId = intval( $projectId );
        
        $repository = new GitRepository();
        $repository->setBackend(new Git_Backend_Gitolite(new Git_GitoliteDriver()));

        if ( $repository->isNameValid($repositoryName) === false ) {
            $c->addError( $this->getText('actions_input_format_error', array($repository->getBackend()->getAllowedCharsInNamePattern(), GitDao::REPO_NAME_MAX_LENGTH)));
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return false;
        }

        $project = ProjectManager::instance()->getProject($projectId);

        $repository->setDescription('-- Default description --');
        $repository->setCreator(UserManager::instance()->getCurrentUser());
        $repository->setProject($project);
        $repository->setName($repositoryName);
        
        if (!$repository->exists()) {
            $repository->create();
        } else {
            $c->addError($this->getText('actions_create_repo_exists', array($repositoryName)));
        }

        $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
        return;
    }

    public function cloneRepository( $projectId, $forkName, $parentId) {
        
        $c         = $this->getController();
        $projectId = intval($projectId);
        $parentId  = intval($parentId);
        if ( empty($projectId) || empty($forkName) || empty($parentId) ) {
            $c->addError($this->getText('actions_params_error'));            
            return false;
        }
        $parentRepo = new GitRepository();
        $parentRepo->setId($parentId);
        try {
            $parentRepo->load();
            
            // Disable possibility to delete gitolite repositories
            if ($parentRepo->getBackend() instanceof Git_Backend_Gitolite) {
                $c->addError( $this->getText('disable_fork_gitolite') );
                $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$parentId.'/');
            }

            if ($parentRepo->isNameValid($forkName) === false) {
                $c->addError( $this->getText('actions_input_format_error', array($parentRepo->getBackend()->getAllowedCharsInNamePattern(), GitDao::REPO_NAME_MAX_LENGTH)));
                $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$parentId.'/');
                return false;
            }

            if ( !$parentRepo->isInitialized() ) {
                $c->addError( $this->getText('repo_not_initialized') );
                $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$parentId.'/');
                return false;
            }
        } catch ( GitDaoException $e ) {
            $c->addError( $e->getMessage() );
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return false;
        }
        $this->systemEventManager->createEvent(
            'GIT_REPO_CLONE',
            $projectId.SystemEvent::PARAMETER_SEPARATOR.$forkName.SystemEvent::PARAMETER_SEPARATOR.$parentId.SystemEvent::PARAMETER_SEPARATOR.$this->user->getId(),
            SystemEvent::PRIORITY_MEDIUM
        );
        $c->addInfo( $this->getText('actions_create_repo_process') );
        $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$parentId.'/');
        return;
    }

    /**
     * Action to load the user's repositories of a project. If user is not given, then load the project repositories instead.
     * 
     * @param int $projectId The project id
     * @param int $userId    The user id. (== null for project repositories)
     *
     * @return bool true if success false otherwise
     */
    public function getProjectRepositoryList($projectId, $userId = null) {
        $onlyGitShell = false;
        $dao          = $this->getDao();
        $this->addData(array(
            'repository_list'     => $dao->getProjectRepositoryList($projectId, $onlyGitShell, $userId),
            'repositories_owners' => $dao->getProjectRepositoriesOwners($projectId),
        ));
        return true;
    }
    
    protected function getDao() {
        return new GitDao();
    }
    
    public function getRepositoryDetails($projectId, $repositoryId) {
        $c = $this->getController();
        $projectId    = intval($projectId);
        $repositoryId = intval($repositoryId);
        if ( empty($repositoryId) ) {
            $c->addError( $this->getText('actions_params_error') );
            return false;
        }
         
        $repository = new GitRepository();
        $repository->setId($repositoryId);        
        try {
            $repository->load();            
        } catch (GitDaoException $e) {
            $c->addError( $this->getText('actions_repo_not_found') );
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return;
        }
        $this->addData( array('repository'=>$repository) );
        return true;
    }

    public function repoManagement($projectId, $repositoryId) {
        $c = $this->getController();
        if (empty($repositoryId)) {
            $c->addError($this->getText('actions_params_error'));
            return false;
        }
        $this->_loadRepository($projectId, $repositoryId);
        return true;
    }

    public function notificationUpdatePrefix($projectId, $repositoryId, $mailPrefix) {
        $c = $this->getController();
        if (empty($repositoryId)) {
            $c->addError($this->getText('actions_params_error'));
            return false;
        }
        $repository = $this->_loadRepository($projectId, $repositoryId);
        $repository->setMailPrefix($mailPrefix);
        $repository->save();
        $repository->changeMailPrefix();
        $c->addInfo($this->getText('mail_prefix_updated'));
        $this->addData(array('repository'=>$repository));
        return true;
    }

    public function notificationAddMail($projectId, $repositoryId, $mails) {
        $c = $this->getController();
        $repository = $this->_loadRepository($projectId, $repositoryId);
        if (empty($repositoryId) || empty($mails)) {
            $c->addError($this->getText('actions_params_error'));
            return false;
        }

        $res = true;
        foreach ($mails as $mail) {
            if ($repository->isAlreadyNotified($mail)) {
                $res = false;
                $c->addInfo($this->getText('mail_existing', array($mail)));
            } else {
                if (!$repository->notificationAddMail($mail)) {
                    $res = false;
                    $c->addError($this->getText('mail_not_added', array($mail)));
                }
            }
        }
        //Display this message, just if all the entred mails have been added
        if ($res) {
            $c->addInfo($this->getText('mail_added'));
        }
        return true;
    }

    public function notificationRemoveMail($projectId, $repositoryId, $mails) {
        $c = $this->getController();
        $repository = $this->_loadRepository($projectId, $repositoryId);
        if (empty($repositoryId) || empty($mails)) {
            $c->addError($this->getText('actions_params_error'));
            return false;
        }
        $ret = true;
        foreach ($mails as $mail) {
            if ($repository->notificationRemoveMail($mail)) {
                $c->addInfo($this->getText('mail_removed', array($mail)));
            } else {
                $c->addError($this->getText('mail_not_removed', array($mail)));
                $ret = false;
            }
        }
        return $ret;
    }

    public function confirmPrivate($projectId, $repoId, $repoAccess, $repoDescription) {
        $c = $this->getController();
        if (empty($repoId) || empty($repoAccess) || empty($repoDescription)) {
            $c->addError($this->getText('actions_params_error'));
            return false;
        }
        $repository = $this->_loadRepository($projectId, $repoId);
        if (strcmp($repoAccess, 'private') == 0 && strcmp($repository->getAccess(), $repoAccess) != 0) {
            $mailsToDelete = $repository->getNonMemberMails();
            if (!empty($mailsToDelete)) {
                $repository->setDescription($repoDescription);
                $repository->save();
                $this->addData(array('repository' => $repository));
                $this->addData(array('mails' => $mailsToDelete));
                $c->addWarn($this->getText('set_private_warn'));
                return true;
            }
        }
        $this->save($projectId, $repoId, $repoAccess, $repoDescription);
        return true;
    }

    public function setPrivate($projectId, $repoId) {
        $c = $this->getController();
        if (empty($repoId)) {
            $c->addError($this->getText('actions_params_error'));
            return false;
        }
        $repository = $this->_loadRepository($projectId, $repoId);
        $mailsToDelete = $repository->getNonMemberMails();
        foreach ($mailsToDelete as $mail) {
            $repository->notificationRemoveMail($mail);
        }
        $this->systemEventManager->createEvent('GIT_REPO_ACCESS',
                                               $repoId.SystemEvent::PARAMETER_SEPARATOR.'private',
                                               SystemEvent::PRIORITY_HIGH);
        $c->addInfo($this->getText('actions_repo_access'));
    }

    public function confirmDeletion($projectId, $repoId) {
        $c = $this->getController();
        if ( empty($repoId) ) {
            $c->addError( $this->getText('actions_params_error') );
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return false;
        }
        $c->addWarn( $this->getText('confirm_deletion_msg'));
    }

    /**
     * This method allows one to save any repository attribues changes from the web interface.
     * @param <type> $repoId
     * @param <type> $repoAccess
     * @param <type> $repoDescription
     * @return <type>
     */
    public function save( $projectId, $repoId, $repoAccess, $repoDescription ) {
        $c = $this->getController();
        if ( empty($repoId) ) {
            $c->addError( $this->getText('actions_params_error') );
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return false;
        }
        if ( empty($repoAccess) || empty($repoDescription) ) {
            $c->addError( $this->getText('actions_params_error') );
            $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$repoId.'/');
            return false;
        }        
        $repository = new GitRepository();
        $repository->setId($repoId);
        try {
            $repository->load();
            if ( !empty($repoAccess) ) {
                if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
                    $repository->getBackend()->savePermissions($repository, $repoAccess);
                } else {
                    if ($repository->getAccess() != $repoAccess) {
                        $this->systemEventManager->createEvent(
                                                      'GIT_REPO_ACCESS',
                                                       $repoId.SystemEvent::PARAMETER_SEPARATOR.$repoAccess,
                                                       SystemEvent::PRIORITY_HIGH
                                                    );
                        $c->addInfo( $this->getText('actions_repo_access') );
                    }
                }
            }
            if (strlen($repoDescription) > 1024) {
                $c->addError( $this->getText('actions_long_description') );
            } elseif (!empty($repoDescription)) {
                $repository->setDescription($repoDescription);
            }
        } catch (GitDaoException $e) {
            $c->addError( $this->getText('actions_repo_not_found') );
            $c->redirect('/plugins/git/?group_id='.$projectId);            
            return false;
        } catch (GitRepositoryException $e1) {
            die('GitRepositoryException');
            $c->addError( $e1->getMessage() );
            return false;
        }

        try {
            $repository->save();
        } catch (GitDaoException $e) {
            $c->addError( $e->getMessage() );             
             $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$repoId.'/');
            return false;
        }
        $c->addInfo( $this->getText('actions_save_repo_process') );
        $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$repoId.'/');
        return;
    }

    /**
     * Internal method called by SystemEvent_PROJECT_IS_PRIVATE
     * @param <type> $projectId
     * @param <type> $isPublic
     * @return <type>
     */
    public static function changeProjectRepositoriesAccess($projectId, $isPrivate) {
        //if the project is private, then no changes may be applied to repositories,
        //in other words only if project is set to private, its repositories have to be set to private
        if ( empty($isPrivate) ) {
            return;
        }
        $dao          = new GitDao();
        $repositories = $dao->getProjectRepositoryList($projectId);
        if ( empty($repositories) ) {
            return false;
        }

        foreach ( $repositories as $repoId=>$repoData ) {
            $r = new GitRepository();
            $r->setId($repoId);
            if ( !$r->exists() ) {
                continue;
            }
            $newAccess = !empty($isPrivate) ? GitRepository::PRIVATE_ACCESS : GitRepository::PUBLIC_ACCESS;
            if ( $r->getAccess() == $newAccess ) {
                continue;
            }
            $r->setAccess( $newAccess );
            $r->changeAccess();
            unset($r);
        }

    }

    /**
     * Method called by SystemEvent_PROJECT_RENAME event
     *
     * @param Project $project Project to modify
     * @param String  $newName New unix group name
     *
     * @return Boolean
     */
    public static function renameProject(Project $project, $newName) {
        $r = new GitRepository();
        return $r->renameProject($project, $newName);
    }

    public static function isNameAvailable($newName, &$error) {
        $b1 = new Git_Backend_Gitolite(new Git_GitoliteDriver());
        $b2 = Backend::instance('Git','GitBackend');
        if (!$b1->isNameAvailable($newName) && !$b2->isNameAvailable($newName)) {
            $error = $GLOBALS['Language']->getText('plugin_git', 'actions_name_not_available');
            return false;
        }
        return true;
    }

    function _loadRepository($projectId, $repositoryId) {
        $repository = $this->getGitRepository();
        $repository->setId($repositoryId);
        try {
            $repository->load();
            $this->addData(array('repository'=>$repository));
        } catch (Exception $e) {
            $c = $this->getController();
            $c->addError($this->getText('actions_repo_not_found'));
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
        }
        return $repository;
    }

    /**
     * Wrapper used for tests to get a new GitRepository
     */
    function getGitRepository() {
        return new GitRepository();
    }

    /**
     * Fork a bunch of repositories in a project for a given user
     *
     * Repositories that the user cannot access won't be forked as well as 
     * those that don't belong to the project.
     * 
     * @param int    $groupId   The project id
     * @param array  $repos_ids The array of id of repositories to fork
     * @param string $path      The path where the new repositories will live
     * @param User   $user      The owner of those new repositories
     * @param Layout $response  The response object
     */
    public function forkIndividualRepositories(array $repos, Project $to_project, $path, User $user, Layout $response) {
        $project_id = $to_project->getId();
        $redirect_url = '/plugins/git/?group_id='. (int)$project_id .'&user='. (int)$user->getId();
        if ($this->fork($repos, $user, $path, GitRepository::REPO_SCOPE_INDIVIDUAL, $to_project)) {
            $this->addInfo('successfully_forked');
            $response->redirect($redirect_url);
        } else {
            $this->addError('actions_no_repository_forked');
        }
    }

    /**
     * Fork a bunch of repositories in an external project for an admin of th e project
     *
     * Repositories those that don't belong to the project.
     *
     * @param array   $repos_ids  The array of id of repositories to fork
     * @param Project $to_project The project where the new repositories will be forked
     * @param User    $user       The owner of those new repositories
     * @param Layout  $response   The response object
     *
     * @return null
     */
    public function forkCrossProjectRepositories(array $repos, Project $to_project, User $user, Layout $response) {
        $project_id = $to_project->getId();
        $namespace  = '';
        $redirect_url = '/plugins/git/?group_id='. (int)$project_id;
        if ($this->fork($repos, $user, $namespace, GitRepository::REPO_SCOPE_PROJECT, $to_project)) {
            $this->addInfo('successfully_forked');
            $response->redirect($redirect_url);
        } else {
            $this->addError('actions_no_repository_forked');
        }
    }
    
    /**
     * Tell the controller to show the error $error
     * 
     * @param string $error error message to show
     */
    protected function addError($key_message) {
        $controler = $this->getController();
        $controler->addError($this->getText($key_message));
    }
    /**
     * Tell the controller to show an info referenced by first parameter
     * 
     * @param string $key_message message to display
     */
    protected function addInfo($key_message) {
        $controler = $this->getController();
        $controler->addInfo($this->getText($key_message));
    }
    
    /**
     * Fork a list of repositories for the given user
     *
     * @param array $repos a list of GitRepository
     * @param User $user
     *
     * @return bool whether dofork was called once or not
     */
    public function fork(array $repos, User $user, $namespace, $scope, Project $project) {
        $forked = false;
        $repos  = array_filter($repos);
        foreach($repos as $repo) {
            try {
                if ($repo->userCanRead($user)) {
                    $this->forkRepo($repo, $user, $namespace, $scope, $project);
                    $forked = true;
                }
            } catch (Exception $e) {
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_git', 'fork_repository_exists', array($repo->getName())));
            }
        }
        return $forked;
    }

    private function forkRepo(GitRepository $repo, User $user, $namespace, $scope, Project $project) {
        $clone = clone $repo;
        $clone->setProject($project);
        $clone->setCreator($user);
        $clone->setParent($repo);
        $clone->setNamespace($namespace);
        $clone->setId(null);
        $path = unixPathJoin(array($project->getUnixName(), $namespace, $repo->getName())).'.git';
        $clone->setPath($path);
        $clone->setScope($scope);
        $repo->getBackend()->fork($repo, $clone);
    }
}

?>
