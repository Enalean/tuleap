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
require_once('events/SystemEvent_GIT_REPO_DELETE.class.php');
require_once('events/SystemEvent_GIT_REPO_ACCESS.class.php');
require_once('events/SystemEvent_GIT_GERRIT_MIGRATION.class.php');
require_once('common/system_event/SystemEventManager.class.php');
require_once('GitBackend.class.php');
require_once('GitRepository.class.php');
require_once('GitDao.class.php');
require_once('Git_GitoliteDriver.class.php');
require_once('Git_Backend_Gitolite.class.php');
require_once('GitRepositoryFactory.class.php');
require_once('GitRepositoryManager.class.php');
require_once('common/layout/Layout.class.php');

require_once GIT_BASE_DIR .'/Git/RemoteServer/GerritServerFactory.class.php';

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
     * @var GitRepositoryFactory 
     */
    private $factory;

    /**
     * @var GitRepositoryManager
     */
    private $manager;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    /** @var Git_Driver_Gerrit */
    private $driver;

    /**
     * Constructor
     *
     * @param Git                  $controller         The controller
     * @param SystemEventManager   $systemEventManager The system manager
     * @param GitRepositoryFactory $factory            The factory to manage repositories
     * @param GitRepositoryManager $manager            The manager to create/delete repositories
     */
    public function __construct(
        Git                $controller,
        SystemEventManager $systemEventManager,
        GitRepositoryFactory $factory,
        GitRepositoryManager $manager,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        Git_Driver_Gerrit $driver
    ) {
        parent::__construct($controller);
        $this->systemEventManager    = $systemEventManager;
        $this->factory               = $factory;
        $this->manager               = $manager;
        $this->gerrit_server_factory = $gerrit_server_factory;
        $this->driver                = $driver;

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
            $this->addError('actions_params_error');
            return false;
        }
        
        $repository = $this->factory->getRepositoryById($repositoryId);
        if ($repository) {
            if ($repository->canBeDeleted()) {
                $this->markAsDeleted($repository);
                $c->addInfo($this->getText('actions_delete_process', array($repository->getFullName())));
                $c->addInfo($this->getText('actions_delete_backup', array($repository->getFullName())).' : '.$c->getPlugin()->getConfigurationParameter('git_backup_dir'));
            } else {
                $this->addError('backend_delete_haschild_error');
                $this->redirectToRepo($projectId, $repositoryId);
                return false;
            }
        } else {
            $this->addError('actions_repo_not_found');
        }
        $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
    }

    private function markAsDeleted(GitRepository $repository) {
        $repository->markAsDeleted();
        $this->systemEventManager->createEvent(
            'GIT_REPO_DELETE',
            $repository->getProjectId().SystemEvent::PARAMETER_SEPARATOR.$repository->getId(),
            SystemEvent::PRIORITY_MEDIUM
        );
    }

    private function createGitshellReference($projectId, $repositoryName) {
        $this->systemEventManager->createEvent(
            'GIT_REPO_CREATE',
            $projectId.SystemEvent::PARAMETER_SEPARATOR.$repositoryName.SystemEvent::PARAMETER_SEPARATOR.UserManager::instance()->getCurrentUser()->getId(),
            SystemEvent::PRIORITY_MEDIUM
        );
        $this->getController()->redirect('/plugins/git/?action=index&group_id='.$projectId);
        exit;
    }
    
    public function createReference($projectId, $repositoryName) {
        // Uncomment the following line only for debug prupose if you ever need to
        // create a gitshell repo (good luck, luke, may the force be with you).
        //$this->createGitshellReference($projectId, $repositoryName);
        $c         = $this->getController();
        $projectId = intval( $projectId );

        try {
            $backend    = new Git_Backend_Gitolite(new Git_GitoliteDriver());
            $repository = new GitRepository();
            $repository->setBackend($backend);
            $repository->setDescription(GitRepository::DEFAULT_DESCRIPTION);
            $repository->setCreator(UserManager::instance()->getCurrentUser());
            $repository->setProject(ProjectManager::instance()->getProject($projectId));
            $repository->setName($repositoryName);

            $this->manager->create($repository, $backend);
        } catch (Exception $exception) {
            $c->addError($exception->getMessage());
        }

        $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
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
        $scope        = true;
        $dao          = $this->getDao();
        $this->addData(array(
            'repository_list'     => $dao->getProjectRepositoryList($projectId, $onlyGitShell, $scope, $userId),
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
            $this->addError('actions_params_error');
            return false;
        }
        $repository = $this->factory->getRepositoryById($repositoryId);
        if (!$repository) {
            $this->addError('actions_repo_not_found');
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return;
        }
        $this->addData(array(
            'repository'     => $repository,
            'gerrit_servers' => $this->gerrit_server_factory->getServers(),
            'driver'         => $this->driver,
        ));
        return true;
    }

    public function repoManagement(GitRepository $repository) {
        $this->addData(array('repository'=>$repository));
        if ($this->systemEventManager->isThereAnEventAlreadyOnGoing(SystemEvent_GIT_GERRIT_MIGRATION::TYPE, $repository->getId())) {
            $GLOBALS['Response']->addFeedback(Feedback::INFO, $this->getText('gerrit_migration_ongoing'));
        }
        $this->addData(array(
            'gerrit_servers' => $this->gerrit_server_factory->getServers(),
            'driver'         => $this->driver,
        ));
        return true;
    }

    public function notificationUpdatePrefix($projectId, $repositoryId, $mailPrefix, $pane) {
        $c = $this->getController();
        if (empty($repositoryId)) {
            $this->addError('actions_params_error');
            return false;
        }
        $repository = $this->_loadRepository($projectId, $repositoryId);
        if ($repository->getMailPrefix() != $mailPrefix) {
            $repository->setMailPrefix($mailPrefix);
            $repository->save();
            $repository->changeMailPrefix();
            $c->addInfo($this->getText('mail_prefix_updated'));
            $this->addData(array('repository'=>$repository));
        }
        return true;
    }

    public function notificationAddMail($projectId, $repositoryId, $mails, $pane) {
        $c = $this->getController();
        $repository = $this->_loadRepository($projectId, $repositoryId);
        if (empty($repositoryId) || empty($mails)) {
            $this->addError('actions_params_error');
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

    public function notificationRemoveMail($projectId, $repositoryId, $mails, $pane) {
        $c = $this->getController();
        $repository = $this->_loadRepository($projectId, $repositoryId);
        if (empty($repositoryId) || empty($mails)) {
            $this->addError('actions_params_error');
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

    public function redirectToRepoManagement($projectId, $repositoryId, $pane) {
        $redirect_url = GIT_BASE_URL .'/?'. http_build_query(
            array(
                'action'   => 'repo_management',
                'group_id' => $projectId,
                'repo_id'  => $repositoryId,
                'pane'     => $pane,
            )
        );
        $this->getController()->redirect($redirect_url);
    }

    public function confirmPrivate($projectId, $repoId, $repoAccess, $repoDescription) {
        $c = $this->getController();
        if (empty($repoId) || empty($repoAccess) || empty($repoDescription)) {
            $this->addError('actions_params_error');
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
            $this->addError('actions_params_error');
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

    /**
     * This method allows one to save any repository attribues changes from the web interface.
     * @param <type> $repoId
     * @param <type> $repoAccess
     * @param <type> $repoDescription
     * @return <type>
     */
    public function save( $projectId, $repoId, $repoAccess, $repoDescription, $pane) {
        $c = $this->getController();
        if ( empty($repoId) ) {
            $this->addError('actions_params_error');
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return false;
        }
        $repository = $this->factory->getRepositoryById($repoId);
        if (! $repository) {
            $this->addError('actions_repo_not_found');
            $c->redirect('/plugins/git/?group_id='.$projectId);
            return false;
        }
        if (empty($repoAccess) && empty($repoDescription)) {
            $this->addError('actions_params_error');
            $this->redirectToRepo($projectId, $repoId);
            return false;
        }

        if ($repoDescription) {
            if (strlen($repoDescription) > 1024) {
                $this->addError('actions_long_description');
            } else {
                $repository->setDescription($repoDescription);
            }
        }

        try {
            $repository->save();
            if ( !empty($repoAccess) ) {
                //TODO use Polymorphism to handle this
                if ($repository->getBackend() instanceof Git_Backend_Gitolite) {
                    //TODO use Polymorphism to handle this
                    if (! $repository->getRemoteServerId()) {
                        $repository->getBackend()->savePermissions($repository, $repoAccess);
                    }
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
            $repository->getBackend()->commitTransaction($repository);
            
        } catch (GitDaoException $e) {
            $c->addError( $e->getMessage() );
            $this->redirectToRepoManagement($projectId, $repoId, $pane);
            return false;
        }
        $c->addInfo( $this->getText('actions_save_repo_process') );
        $this->redirectToRepoManagement($projectId, $repoId, $pane);
        return;
    }

    /**
     * Internal method called by SystemEvent_PROJECT_IS_PRIVATE
     * @param <type> $projectId
     * @param <type> $isPublic
     * @return <type>
     */
    public static function changeProjectRepositoriesAccess($projectId, $isPrivate, GitDao $dao, GitRepositoryFactory $factory) {
        //if the project is private, then no changes may be applied to repositories,
        //in other words only if project is set to private, its repositories have to be set to private
        if ( empty($isPrivate) ) {
            return;
        }
        $repositories = $dao->getProjectRepositoryList($projectId);
        foreach ( $repositories as $repoId=>$repoData ) {
            $r = $factory->getRepositoryById($repoId);
            if ( !$r ) {
                continue;
            }
            if ( $r->getAccess() == GitRepository::PRIVATE_ACCESS) {
                continue;
            }
            $r->setAccess( GitRepository::PRIVATE_ACCESS );
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
        $repository = $this->getGitRepository($repositoryId);
        if ($repository) {
            $this->addData(array('repository'=>$repository));
            return $repository;
        } else {
            $c = $this->getController();
            $this->addError('actions_repo_not_found');
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
        }
    }

    /**
     * Wrapper used for tests to get a new GitRepository
     */
    function getGitRepository($repositoryId) {
        return $this->factory->getRepositoryById($repositoryId);
    }

    /**
     * Fork a bunch of repositories in a project for a given user
     * 
     * @param int    $groupId         The project id
     * @param array  $repos_ids       The array of id of repositories to fork
     * @param string $namespace       The namespace where the new repositories will live
     * @param User   $user            The owner of those new repositories
     * @param Layout $response        The response object
     * @param array  $forkPermissions Permissions to be applied for the new repository
     */
    public function fork(array $repos, Project $to_project, $namespace, $scope, User $user, Layout $response, $redirect_url, array $forkPermissions) {
        try {
            if ($this->manager->forkRepositories($repos, $to_project, $user, $namespace, $scope, $forkPermissions)) {
                $GLOBALS['Response']->addFeedback('info', $this->getText('successfully_forked'));
                $response->redirect($redirect_url);
            }
        } catch(Exception $e) {
            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
        }
    }

    /**
     * Prepare data for fork permissions action
     *
     * @param array  $repos     Repositories Ids we want to fork
     * @param array  $project   The project Id where repositories would be forked
     * @param string $namespace The namespace where the new repositories will live
     * @param string $scope     The scope of the fork: personal or cross project.
     *
     * @return void
     */
    public function forkRepositoriesPermissions($repos, $project, $namespace, $scope) {
        $this->addData(array('repos'     => join(',', $repos),
                             'group_id'  => $project,
                             'namespace' => $namespace,
                             'scope'     => $scope));
    }

    /**
     * 
     * @param GitRepository $repository
     * @param int $remote_server_id the id of the server to which we want to migrate
     */
    public function migrateToGerrit(GitRepository $repository, $remote_server_id) {
        if ($repository->canMigrateToGerrit()) {
            try {
                $this->gerrit_server_factory->getServerById($remote_server_id);
                $this->systemEventManager->createEvent(
                    SystemEvent_GIT_GERRIT_MIGRATION::TYPE,
                    $repository->getId() . SystemEvent::PARAMETER_SEPARATOR . $remote_server_id,
                    SystemEvent::PRIORITY_HIGH
                );
            } catch (Git_RemoteServer_NotFoundException $e) {
                // TODO log error to the syslog
            }
        }
    }
    
    private function redirectToRepo($projectId, $repoId) {
        $this->getController()->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$repoId.'/');
    }

    private function addError($error_key) {
        $this->getController()->addError($this->getText($error_key));
    }
    
    
}

?>
