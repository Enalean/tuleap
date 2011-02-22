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

/**
 * GitActions
 * @todo call Event class instead of SystemEvent
 * @author Guillaume Storchi
 */
class GitActions extends PluginActions {


    public function __construct($controller) {
        parent::__construct($controller);
        $this->systemEventManager = SystemEventManager::instance();

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
        $repository   = new GitRepository();
        $repository->setId( $repositoryId );
        if ( $repository->hasChild() ) {
            $c->addError( $this->getText('backend_delete_haschild_error') );
            $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$repositoryId.'/');
            return false;
        }
        $this->systemEventManager->createEvent(
            'GIT_REPO_DELETE',
            $projectId.SystemEvent::PARAMETER_SEPARATOR.$repositoryId,
            SystemEvent::PRIORITY_MEDIUM
        );       
        $c->addInfo( $this->getText('actions_delete_process') );
        $c->addInfo( $this->getText('actions_delete_backup').' : '.PluginManager::instance()->getPluginByName('git')->getPluginInfo()->getPropVal('git_backup_dir') );
        $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
    }
       
	public function createReference( $projectId, $repositoryName) {
        $c              = $this->getController();
        $projectId      = intval( $projectId );
        if ( empty($repositoryName) ) {
            $c->addError($this->getText('actions_params_error'));
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return false;
        }
        if ( GitDao::checkName($repositoryName) === false ) {
            $c->addError( $this->getText('actions_input_format_error').' '.GitDao::REPO_NAME_MAX_LENGTH);
            $c->redirect('/plugins/git/?action=index&group_id='.$projectId);
            return false;
        }
        $this->systemEventManager->createEvent(
            'GIT_REPO_CREATE',
            $projectId.SystemEvent::PARAMETER_SEPARATOR.$repositoryName.SystemEvent::PARAMETER_SEPARATOR.$this->user->getId(),
            SystemEvent::PRIORITY_MEDIUM
        );
        $c->addInfo( $this->getText('actions_create_repo_process') );
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
        if ( GitDao::checkName($forkName) === false ) {
            $c->addError( $this->getText('actions_input_format_error').' '.GitDao::REPO_NAME_MAX_LENGTH );
            $c->redirect('/plugins/git/index.php/'.$projectId.'/view/'.$parentId.'/');
            return false;
        }
        $parentRepo = new GitRepository();
        $parentRepo->setId($parentId);
        try {
            $parentRepo->load();
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

    public function getProjectRepositoryList($projectId) {
        $projectId = intval($projectId);              
        $dao       = new GitDao();        
        $repositoryList = $dao->getProjectRepositoryList($projectId);        
        $this->addData( array('repository_list'=>$repositoryList) );        
        return true;
    }
    
    //TODO check repo - project?
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
            if ( !empty($repoAccess) && $repository->getAccess() != $repoAccess) {
                $this->systemEventManager->createEvent(
                                              'GIT_REPO_ACCESS',
                                               $repoId.SystemEvent::PARAMETER_SEPARATOR.$repoAccess,
                                               SystemEvent::PRIORITY_HIGH
                                            );
                $c->addInfo( $this->getText('actions_repo_access') );
            }
            if ( !empty($repoDescription) ) {
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
        $r = new GitRepository();
        if (! $r->isNameAvailable($newName)) {
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

}


?>