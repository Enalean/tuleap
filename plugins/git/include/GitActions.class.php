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
require_once('common/include/HTTPRequest.class.php');
require_once('events/SystemEvent_GIT_REPO_CREATE.class.php');
require_once('events/SystemEvent_GIT_REPO_CLONE.class.php');
require_once('events/SystemEvent_GIT_REPO_DELETE.class.php');
require_once('events/SystemEvent_GIT_REPO_ACCESS.class.php');
require_once('common/system_event/SystemEventManager.class.php');
require_once('GitBackend.class.php');
require_once('GitRepository.class.php');
require_once('GitDao.class.php');
require_once('common/include/Codendi_HTMLPurifier.class.php');

/**
 * GitActions
 * @author Guillaume Storchi
 */
class GitActions extends PluginActions {


    public function __construct($controller) {
        parent::__construct($controller);
        $this->systemEventManager = SystemEventManager::instance();
	}

    protected function getText($key) {
        return $GLOBALS['Language']->getText('plugin_git', $key);
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
            $c->redirect('/plugins/git/?action=view&group_id='.$projectId.'&repo_id='.$repositoryId);
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
            $c->redirect('/plugins/git/?action=view&group_id='.$projectId.'&repo_id='.$parentId);
            return false;
        }
        $parentRepo = new GitRepository();
        $parentRepo->setId($parentId);
        try {
            $parentRepo->load();
            if ( !$parentRepo->isInitialized() ) {
                $c->addError( $this->getText('repo_not_initialized') );
                $c->redirect('/plugins/git/?action=view&group_id='.$projectId.'&repo_id='.$parentId);
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
        $c->redirect('/plugins/git/?action=view&group_id='.$projectId.'&repo_id='.$parentId );
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
            $c->redirect('/plugins/git/?action=view&repo_id='.$repoId.'&group_id='.$projectId);
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
            $c->redirect('/plugins/git/?action=view&repo_id='.$repoId.'&group_id='.$projectId);
            return false;
        }
        $c->addInfo( $this->getText('actions_save_repo_process') );
        $c->redirect('/plugins/git/?action=view&group_id='.$projectId.'&repo_id='.$repoId );
        return;
    }


    
}


?>
