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

require_once('mvc/PluginController.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('GitViews.class.php');
require_once('GitActions.class.php');
require_once('GitRepository.class.php');
require_once('common/user/UserManager.class.php');
/**
 * Git
 * @author Guillaume Storchi
 */
class Git extends PluginController {
    
    public function __construct(GitPlugin $plugin) {

        parent::__construct();
        $this->request     = HTTPRequest::instance();
        $this->plugin      = $plugin;
        $this->groupId     = (int)$this->request->get('group_id');
        $this->action      = $this->request->get('action');
        $this->nonMember   = false;
        if (  empty($this->action) ) {
            $this->action = 'index';
        }
        if ( empty($this->groupId) ) {
            $this->addError('Bad request');
            $this->redirect('/');
        }        
        
        $this->projectName      = ProjectManager::instance()->getProject($this->groupId)->getUnixName();
        if ( !PluginManager::instance()->isPluginAllowedForProject($this->plugin, $this->groupId) ) {
            $this->addError( $this->getText('project_service_not_available') );
            $this->redirect('/projects/'.$this->projectName.'/');
        }

        $this->permittedActions = array();
        //user access control
        if ( $this->user->isMember($this->groupId, 'A') === true ) {
            $this->permittedActions = array('index','view', 'edit', 'clone', 'add', 'del', 'create', 'confirm_deletion', 'save');
        } else if ( $this->user->isMember($this->groupId) === true ) {
            $this->permittedActions = array('index','view', 'edit', 'clone');
        } else if ( !$this->user->isRestricted() ) {
            //public repository access
            $this->permittedActions = array('index');
            $this->nonMember        = true;
        }        

        if ( empty($this->permittedActions) ) {
            $this->addError( $this->getText('controller_access_denied') );
            $this->redirect('/projects/'.$this->projectName.'/');
        }                
    }

    protected function getText($key) {
        return $GLOBALS['Language']->getText('plugin_git', $key);
    }

    public function request() {
        
        $repositoryName = $this->request->get('repo_name');
        $description    = $this->request->get('repo_desc');
        $repoId         = $this->request->get('repo_id');
        //public access
        if ( !empty($repoId) && $this->nonMember ) {
            $repo = new GitRepository();
            $repo->setId($repoId);
            if ( $repo->exists() && $repo->isPublic() ) {
                $this->addPermittedAction('view');
            }
        }
        //check permissions
        if (  !empty($this->action) && !$this->isAPermittedAction($this->action) ) {
            $this->addError( $this->getText('controller_action_permission_denied') );
            $this->redirect('?group_id='.$this->groupId);
            return;
        }

        switch ($this->action) {
            #CREATE REF
            case 'create':
                $this->addView('create');
                break;
            #admin
            case 'view':
                $this->addAction( 'getRepositoryDetails', array($this->groupId, $repoId) );
                $this->addView('view');
                break;
           
            #ADD REF
            case 'add':
                $this->addAction('createReference', array($this->groupId, $repositoryName) );
                $this->addView('index');
                break;
             #DELETE a repository
            case 'del':                
                $this->addAction( 'deleteRepository', array($this->groupId, $repoId) );
                $this->addView('index');
                break;
            #EDIT
            case 'edit':                
                if ( $this->isAPermittedAction('clone') && $this->request->get('clone') ) {
                    $parentId = (int)$this->request->get('parent_id');
                    $this->addAction( 'cloneRepository', array($this->groupId, $repositoryName, $parentId) );
                    $this->addAction( 'getRepositoryDetails', array($this->groupId, $parentId) );
                    $this->addView('view');
                }
                else if ( $this->isAPermittedAction('confirm_deletion') && $this->request->get('confirm_deletion') ) {
                    $this->addAction('confirmDeletion', array($this->groupId, $repoId) );
                    $this->addView('confirm_deletion', array( 0=>array('repo_id'=>$repoId) ) );
                }
                else if ( $this->isAPermittedAction('save') && $this->request->get('save') ) {                    
                    $repoDesc   = $this->request->get('repo_desc');
                    $repoAccess = $this->request->get('repo_access');
                    $this->addAction('save', array($this->groupId, $repoId, $repoAccess, $repoDesc) );
                    $this->addView('view');
                } else {
                    $this->addError( $this->getText('controller_action_permission_denied') );
                    $this->redirect('/plugins/git/?group_id='.$this->groupId);
                }
                break;
            #LIST
            default:     
                $this->addAction( 'getProjectRepositoryList', array($this->groupId) );                
                $this->addView('index');
                break;
        }
    }

    
}

?>
