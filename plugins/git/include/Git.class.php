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
require_once('GitViews.class.php');
require_once('GitActions.class.php');
require_once('GitRepository.class.php');
/**
 * Git
 * @author Guillaume Storchi
 */
class Git extends PluginController {
    const PERM_READ  = 'PLUGIN_GIT_READ';
    const PERM_WRITE = 'PLUGIN_GIT_WRITE';
    const PERM_WPLUS = 'PLUGIN_GIT_WPLUS';

    public function __construct(GitPlugin $plugin) {
        
        $matches = array();
        parent::__construct();
        
        if ( preg_match_all('/^\/plugins\/git\/index.php\/(\d+)\/([^\/][a-zA-Z]+)\/([a-zA-Z\-\_0-9]+)\/\?{0,1}.*/', $_SERVER['REQUEST_URI'], $matches) ) {
            $this->request->set('group_id', $matches[1][0]);
            $this->request->set('action', $matches[2][0]);
            $repo_id = 0;            
            //repository id is passed            
            if ( preg_match('/^([0-9]+)$/', $matches[3][0]) === 1 ) {
               $repo_id = $matches[3][0];
            } else {
            //get repository by name and group id to retrieve repo id
               $repo = new GitRepository();
               $repo->setName($matches[3][0]);
               $repo->setProject( ProjectManager::instance()->getProject($matches[1][0]) );
               try {
                   $repo->load();
               } catch (Exception $e) {                   
                   $this->addError('Bad request');
                   $this->redirect('/');                   
               }
               $repo_id = $repo->getId();               
            }
            $this->request->set('repo_id', $repo_id);
        }        
        $this->plugin      = $plugin;
        $valid = new Valid_GroupId('group_id');
        $valid->required();
        if($this->request->valid($valid)) {
            $this->groupId = (int)$this->request->get('group_id');
        }
        $valid = new Valid_String('action');
        $valid->required();
        if($this->request->valid($valid)) {
            $this->action = $this->request->get('action');
        }
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
            $this->permittedActions = array('index',
                                            'view' ,
                                            'edit',
                                            'clone',
                                            'add',
                                            'del',
                                            'create',
                                            'confirm_deletion',
                                            'save',
                                            'repo_management',
                                            'mail_prefix',
                                            'add_mail',
                                            'remove_mail',
                                            'fork',
                                            'set_private',
                                            'confirm_private');
        } else if ( $this->user->isMember($this->groupId) === true ) {
            $this->permittedActions = array('index','view', 'edit', 'clone');
        } else if ( !$this->user->isAnonymous() && !$this->user->isRestricted() ) {
            //public repository access
            $this->permittedActions = array('index');
            $this->nonMember        = true;
        }

        if ( empty($this->permittedActions) ) {            
            $this->addError( $this->getText('controller_access_denied') );
            $this->redirect('/projects/'.$this->projectName.'/');
        }                
    }

    protected function getText($key, $params = array()) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }

    public function request() {
        $valid = new Valid_String('repo_name');
        $valid->required();
        if($this->request->valid($valid)) {
            $repositoryName = $this->request->get('repo_name');
        }
        $valid = new Valid_UInt('repo_id');
        $valid->required();
        if($this->request->valid($valid)) {
            $repoId = $this->request->get('repo_id');
        } else {
            $repoId = 0;
        }

        $user = UserManager::instance()->getCurrentUser();

        //public access
        if ($repoId !== 0) {
            $repo = new GitRepository();
            $repo->setId($repoId);
            if ($repo->exists() && $repo->userCanRead($user)) {
                $this->addPermittedAction('view');
            }
        } 

        //check permissions
        if (!empty($this->action) && !$this->isAPermittedAction($this->action)) {
            $this->addError($this->getText('controller_action_permission_denied'));
            $this->redirect('/plugins/git/?group_id='.$this->groupId);
            return;
        }

        $this->_informAboutPendingEvents($repoId);

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
                if ($user->useLabFeatures() && $this->request->existAndNonEmpty('repo_type')) {
                    $backendType = GitDao::BACKEND_GITOLITE;
                } else {
                    $backendType = GitDao::BACKEND_GITSHELL;
                }
                $this->addAction('createReference', array($this->groupId, $repositoryName, $backendType) );
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
                    $valid = new Valid_UInt('parent_id');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $parentId = (int)$this->request->get('parent_id');
                    }
                    $this->addAction( 'cloneRepository', array($this->groupId, $repositoryName, $parentId) );
                    $this->addAction( 'getRepositoryDetails', array($this->groupId, $parentId) );
                    $this->addView('view');
                }
                else if ( $this->isAPermittedAction('confirm_deletion') && $this->request->get('confirm_deletion') ) {
                    $this->addAction('confirmDeletion', array($this->groupId, $repoId) );
                    $this->addView('confirm_deletion', array( 0=>array('repo_id'=>$repoId) ) );
                }
                else if ( $this->isAPermittedAction('save') && $this->request->get('save') ) {                    
                    $valid = new Valid_String('repo_desc');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $repoDesc = $this->request->get('repo_desc');
                    }
                    $valid = new Valid_String('repo_access');
                    $valid->required();
                    if($this->request->valid($valid) || is_array($this->request->get('repo_access'))) {
                        $repoAccess = $this->request->get('repo_access');
                    }
                    $this->addAction('save', array($this->groupId, $repoId, $repoAccess, $repoDesc) );
                    $this->addView('view');
                } else {
                    $this->addError( $this->getText('controller_action_permission_denied') );
                    $this->redirect('/plugins/git/?group_id='.$this->groupId);
                }
                break;
            #repo_management
            case 'repo_management':
                $this->addAction('repoManagement', array($this->groupId, $repoId));
                $this->addView('repoManagement');
                break;
            #mail prefix
            case 'mail_prefix':
                $valid = new Valid_String('mail_prefix');
                $valid->required();
                if($this->request->valid($valid)) {
                    $mailPrefix = $this->request->get('mail_prefix');
                } else {
                    $mailPrefix = '';
                }
                $this->addAction('notificationUpdatePrefix', array($this->groupId, $repoId, $mailPrefix));
                $this->addView('repoManagement');
                break;
            #add mail
            case 'add_mail':
                $validMails = array();
                $mails      = array_map('trim', preg_split('/[,;]/', $this->request->get('add_mail')));
                $rule       = new Rule_Email();
                $um         = UserManager::instance();
                foreach ($mails as $mail) {
                    if ($rule->isValid($mail)) {
                        $validMails[] = $mail;
                    } else {
                        $user = $um->findUser($mail);
                        if ($user) {
                            $mail = $user->getEmail();
                            if ($mail) {
                                $validMails[] = $mail;
                            } else {
                                $this->addError($this->getText('no_user_mail', array($mail)));
                            }
                        } else {
                            $this->addError($this->getText('no_user', array($mail)));
                        }
                    }
                }
                $this->addAction('notificationAddMail', array($this->groupId, $repoId, $validMails));
                $this->addView('repoManagement');
                break;
            #remove mail
            case 'remove_mail':
                $mails = array();
                $valid = new Valid_Email('mail');
                $valid->required();
                if($this->request->validArray($valid)) {
                    $mails = $this->request->get('mail');
                }
                if (count($mails) > 0) {
                    $this->addAction('notificationRemoveMail', array($this->groupId, $repoId, $mails));
                    $this->addView('repoManagement');
                } else {
                    $this->addAction('repoManagement', array($this->groupId, $repoId));
                    $this->addView('repoManagement');
                }
                break;
            #fork
            case 'fork':
                $this->addAction('repoManagement', array($this->groupId, $repoId));
                $this->addView('fork');
                break;
            #confirm_private
            case 'confirm_private':
                if ( $this->isAPermittedAction('confirm_deletion') && $this->request->get('confirm_deletion') ) {
                    $this->addAction('confirmDeletion', array($this->groupId, $repoId) );
                    $this->addView('confirm_deletion', array( 0=>array('repo_id'=>$repoId) ) );
                }
                else if ( $this->isAPermittedAction('save') && $this->request->get('save') ) {
                    $valid = new Valid_String('repo_desc');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $repoDesc = $this->request->get('repo_desc');
                    }
                    $valid = new Valid_String('repo_access');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $repoAccess = $this->request->get('repo_access');
                    }
                    $this->addAction('confirmPrivate', array($this->groupId, $repoId, $repoAccess, $repoDesc) );
                    $this->addView('confirmPrivate');
                }
                break;
             #SET TO PRIVATE
            case 'set_private':
                $this->addAction('setPrivate', array($this->groupId, $repoId));
                $this->addView('view');
                break;
            #LIST
            default:     
                $this->addAction( 'getProjectRepositoryList', array($this->groupId) );                
                $this->addView('index');
                break;
        }
    }

    protected function _informAboutPendingEvents($repoId) {
        $sem = SystemEventManager::instance();
        $dar = $sem->_getDao()->searchWithParam('head', $this->groupId, array('GIT_REPO_CREATE', 'GIT_REPO_CLONE', 'GIT_REPO_DELETE'), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING));
        foreach ($dar as $row) {
            switch($row['type']) {
            case 'GIT_REPO_CREATE':
                $p = explode('::', $row['parameters']);
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_create', array($p[1])));
                break;

            case 'GIT_REPO_CLONE':
                $p = explode('::', $row['parameters']);
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_fork', array($p[1])));
                break;

            case 'GIT_REPO_DELETE':
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_delete'));
                break;
            }
            
        }

        if ($repoId !== 0) {
            $dar = $sem->_getDao()->searchWithParam('head', $repoId, array('GIT_REPO_ACCESS'), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING));
            foreach ($dar as $row) {
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_access'));
            }
        }
    }
}

?>
