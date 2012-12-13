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

require_once GIT_BASE_DIR .'/mvc/PluginViews.class.php';
require_once GIT_BASE_DIR .'/gitPlugin.class.php';
require_once GIT_BASE_DIR .'/GitDao.class.php';
require_once GIT_BASE_DIR .'/Git_LogDao.class.php';
require_once GIT_BASE_DIR .'/GitBackend.class.php';
require_once GIT_BASE_DIR .'/GitViewsRepositoriesTraversalStrategy_Selectbox.class.php';
require_once GIT_BASE_DIR .'/GitViewsRepositoriesTraversalStrategy_Tree.class.php';
require_once 'www/project/admin/permissions.php';
require_once 'common/include/CSRFSynchronizerToken.class.php';
require_once 'GitViews/RepoManagement/RepoManagement.class.php';

/**
 * GitViews
 */
class GitViews extends PluginViews {

    public function __construct($controller) {
        parent::__construct($controller);
        $this->groupId     = (int)$this->request->get('group_id');
        $this->project     = ProjectManager::instance()->getProject($this->groupId);
        $this->projectName = $this->project->getUnixName();
        $this->userName    = $this->user->getName();        
    }

    public function header() {
        $title = $GLOBALS['Language']->getText('plugin_git','title');
        $GLOBALS['HTML']->header(array('title'=>$title,'group'=>$this->groupId, 'toptab'=>'plugin_git'));
    }

    public function footer() {
        $GLOBALS['HTML']->footer(array());
    }

    public function getText($key, $params=array() ) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }

    /**
     * HELP VIEW
     */
    public function help($topic, $params=array()) {
        if ( empty($topic) ) {
            return false;
        }
        $display = 'block';
        if ( !empty($params['display']) ) {
            $display = $params['display'];
        }
        switch( $topic ) {
                case 'init':
             ?>
<div id="help_init" class="help" style="display:<?php echo $display?>">
    <h3><?php echo $this->getText('help_reference_title'); ?></h3>
    <p>
                       <?php
                       echo '<ul>'.$this->getText('help_init_reference').'</ul>';
                       ?>
    </p>
    </div>
                    <?php
                    break;
                    case 'create':
                        ?>                        
                        <div id="help_create" class="help" style="display:<?php echo $display?>">
                            <h3><?php echo $this->getText('help_create_reference_title'); ?></h3>
                        <?php
                        echo '<ul>'.$this->getText('help_create_reference').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'tree':
                        ?>
                        <div id="help_tree" class="help" style="display:<?php echo $display?>">                            
                        <?php
                        echo '<ul>'.$this->getText('help_tree').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'fork':
                        ?>
                        <div id="help_fork" class="help" style="display:<?php echo $display?>">
                        <?php
                        echo '<ul>'.$this->getText('help_fork').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                default:
                    break;
            }            
        }      

        /**
         * REPO VIEW
         */
        public function view() {
            $gitphp      = '';
            $params       = $this->getData();
            if ( empty($params['repository']) ) {
                $this->getController()->redirect('/plugins/git/?action=index&group_id='.$this->groupId);
                return false;
            }
            $repository   = $params['repository'];
            $repoId       = $repository->getId();
            $repoName     = $repository->getName();
            $initialized  = $repository->isInitialized();
            $creator      = $repository->getCreator();
            $parent       = $repository->getParent();
            $access       = $repository->getAccess();
            $description  = $repository->getDescription();
            $creatorName  = '';
            if ( !empty($creator) ) {
                $creatorName  = UserHelper::instance()->getLinkOnUserFromUserId($creator->getId());
            }
            $creationDate = html_time_ago(strtotime($repository->getCreationDate()));

            ob_start();
            $this->getView($repository);
            $gitphp = ob_get_contents();
            ob_end_clean();

            //download
            if ( $this->request->get('noheader') == 1 ) {
                die($gitphp);
            }

            echo '<br />';
            if ( !$initialized ) {
                $this->help('init', array('repository'=>$repository));
            }
            $this->_getBreadCrumb();
            
            // Access type
            $accessType = $this->fetchAccessType($access, $repository->getBackend() instanceof Git_Backend_Gitolite);

            // Actions
            $repoActions = '<ul id="plugin_git_repository_actions">';
            if ($this->getController()->isAPermittedAction('repo_management')) {
                $repoActions .= '<li>'.$this->linkTo($this->getText('admin_repo_management'), '/plugins/git/?action=repo_management&group_id='.$this->groupId.'&repo_id='.$repoId, 'class="repo_admin"').'</li>';
            }

            /** Disable fork of gitshell repositories **/
            /*
            if ($initialized && $this->getController()->isAPermittedAction('clone') && !($repository->getBackend() instanceof Git_Backend_Gitolite)) {
                $repoActions .= '<li>'.$this->linkTo($this->getText('admin_fork_creation_title'), '/plugins/git/?action=fork&group_id='.$this->groupId.'&repo_id='.$repoId, 'class="repo_fork"').'</li>';
            }
            */
            $repoActions .= '</ul>';

            echo '<div id="plugin_git_reference">';
            echo '<h1>'.$accessType.$repository->getFullName().'</h1>';
            echo $repoActions;
            echo '<p id="plugin_git_reference_author">'. 
                $this->getText('view_repo_creator') 
                .' '. 
                $creatorName
                .' '.
                $creationDate
                .'</p>';
            ?>
    <?php
    if ( !empty($parent) ) :
    ?>
    <p id="plugin_git_repo_parent"><?php echo $this->getText('view_repo_parent');
            ?>: <span><?php echo $this->_getRepositoryPageUrl( $parent->getId(), $parent->getName() );?></span>
    </p>
    <?php
    endif;
    
    ?>
    <p id="plugin_git_clone_url">
        <?php
        $hp = Codendi_HTMLPurifier::instance();
        $urls = $repository->getAccessURL();
        list(,$first_url) = each($urls);
        if (count($urls) > 1) {
            $selected = 'checked="checked"';
            foreach ($urls as $transport => $url) {
                echo '<label>';
                echo '<input type="radio" class="plugin_git_transport" name="plugin_git_transport" value="'. $hp->purify($url) .'" '.$selected.' />';
                echo $transport;
                echo '</label> ';
                $selected  = '';
            }
        }
        ?>
        <input id="plugin_git_clone_field" type="text" value="<?= $first_url; ?>" /></label>
    </p>
        <?php
        echo '</div>';
        echo $gitphp;
    }

    /**
     * REPOSITORY MANAGEMENT VIEW
     */
    public function repoManagement() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $repoName     = $repository->getName();
        echo "<br/>";
        $this->_getBreadCrumb();
        echo '<h2>'. $this->_getRepositoryPageUrl($repoId, $repoName) .' - '. $this->getText('admin_repo_management') .'</h2>';

        $repo_management_view = new GitViews_RepoManagement($repository, $this->controller->getRequest(), $params['gerrit_servers']);
        $repo_management_view->display();
    }
    
    /**
     * FORK VIEW
     */
    public function fork() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $repoName     = $repository->getName();
        $initialized  = $repository->isInitialized();
        echo "<br/>";
        $this->_getBreadCrumb();
        echo "<h1>".$this->_getRepositoryPageUrl($repoId, $repoName)."</h1>";
        ?>
        <form id="repoAction" name="repoAction" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId?>">
        <input type="hidden" id="action" name="action" value="edit" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId?>" />
        <?php
        if ( $initialized && $this->getController()->isAPermittedAction('clone') ) :
        ?>
            <p id="plugin_git_fork_form">
                <input type="hidden" id="parent_id" name="parent_id" value="<?php echo $repoId?>">
                <label for="repo_name"><?php echo $this->getText('admin_fork_creation_input_name');
        ?>:     </label>
                <input type="text" id="repo_name" name="repo_name" value="" /><input type="submit" name="clone" value="<?php echo $this->getText('admin_fork_creation_submit');?>" />
                <a href="#" onclick="$('help_fork').toggle();"> [?]</a>
            </p>
        </form>
        <?php
        endif;
        $this->help('fork', array('display'=>'none'));
    }

    /**
     * CONFIRM PRIVATE
     */
    public function confirmPrivate() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $repoName     = $repository->getName();
        $initialized  = $repository->isInitialized();
        $mails        = $params['mails'];
        if ( $this->getController()->isAPermittedAction('save') ) :
        ?>
        <div class="confirm">
        <h3><?php echo $this->getText('set_private_confirm'); ?></h3>
        <form id="confirm_private" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId; ?>" >
        <input type="hidden" id="action" name="action" value="set_private" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId; ?>" />
        <input type="submit" id="submit" name="submit" value="<?php echo $this->getText('yes') ?>"/><span><input type="button" value="<?php echo $this->getText('no')?>" onclick="window.location='/plugins/git/?action=view&group_id=<?php echo $this->groupId;?>&repo_id=<?php echo $repoId?>'"/> </span>
        </form>
        <h3><?php echo $this->getText('set_private_mails'); ?></h3>
    <table>
        <?php
        $i = 0;
        foreach ($mails as $mail) {
            echo '<tr class="'.html_get_alt_row_color(++$i).'">';
            echo '<td>'.$mail.'</td>';
            echo '</tr>';
        }
        ?>
    </table>
    </div>
        <?php
        endif;
    }

    /**
     * TREE VIEW
     */
    public function index() {        
        $params = $this->getData();
        $this->_getBreadCrumb();
        $this->_tree($params);
        if ( $this->getController()->isAPermittedAction('add') ) {
            $this->_createForm();
        }
    }

    /**
     * Configure gitphp output
     * 
     * @param GitRepository $repository
     */
    public function getView($repository) {
        include_once 'common/include/Codendi_HTMLPurifier.class.php';
        if ( empty($_REQUEST['a']) )  {
            $_REQUEST['a'] = 'summary';
        }
        set_time_limit(300);
        $_GET['a'] = $_REQUEST['a'];        
        $_REQUEST['group_id']      = $this->groupId;
        $_REQUEST['repo_id']       = $repository->getId();
        $_REQUEST['repo_name']     = $repository->getFullName();
        $_GET['p']                 = $_REQUEST['repo_name'].'.git';
        $_REQUEST['repo_path']     = $repository->getPath();
        $_REQUEST['project_dir']   = $repository->getProject()->getUnixName();
        $_REQUEST['git_root_path'] = $repository->getGitRootPath();
        $_REQUEST['action']        = 'view';
        if ( empty($_REQUEST['noheader']) ) {
            //echo '<hr>';
            echo '<div id="gitphp">';
        }

        include($this->getGitPhpIndexPath());

        if ( empty($_REQUEST['noheader']) ) {
            echo '</div>';
        }
    }

    /**
     * Return path to GitPhp index file
     *
     * @return String
     */
    private function getGitPhpIndexPath() {
        $gitphp_path = $this->getController()->getPlugin()->getConfigurationParameter('gitphp_path');
        if ($gitphp_path) {
            $this->initGitPhpEnvironement();
        } else {
            $gitphp_path = GIT_BASE_DIR .'/../gitphp';
        }
        return $gitphp_path.'/index.php';
    }

    private function initGitPhpEnvironement() {
        define('GITPHP_CONFIGDIR', GIT_BASE_DIR .'/../etc/');
        ini_set('include_path', '/usr/share/gitphp-tuleap:'.ini_get('include_path'));
    }

    /**
     * CREATE REF FORM
     */
    protected function _createForm() {
        $user = UserManager::instance()->getCurrentUser();
        ?>
<h3><?php echo $this->getText('admin_reference_creation_title');
        ?><a href="#" onclick="$('help_create').toggle();$('help_init').toggle()"> [?]</a></h3>
<p>
<form id="addRepository" action="/plugins/git/?group_id=<?php echo $this->groupId ?>" method="POST">
    <input type="hidden" id="action" name="action" value="add" />
    
    <label for="repo_name"><?= $this->getText('admin_reference_creation_input_name'); ?></label>
    <input id="repo_name" name="repo_name" class="" type="text" value=""/>

    <input type="submit" id="repo_add" name="repo_add" value="<?php echo $this->getText('admin_reference_creation_submit')?>">
</form>
</p>
        <?php
        $this->help('create', array('display'=>'none')) ;
        $this->help('init', array('display'=>'none')) ;
    }

    /**
     * @todo make a breadcrumb out of the repository hierarchie ?
     */
    protected function _getBreadCrumb() {
        echo $this->linkTo( '<b>'.$this->getText('bread_crumb_home').'</b>', '/plugins/git/?group_id='.$this->groupId, 'class=""');
        echo ' | ';

        echo $this->linkTo( '<b>'.$this->getText('fork_repositories').'</b>', '/plugins/git/?group_id='.$this->groupId .'&action=fork_repositories', 'class=""');
        echo ' | ';

        echo $this->linkTo( '<b>'.$this->getText('bread_crumb_help').'</b>', 'javascript:help_window(\'/documentation/user_guide/html/'.$this->user->getLocale().'/VersionControlWithGit.html\')');
    }
    
    /**
     * @todo several cases ssh, http ...
     * @param <type> $repositoryName
     * @return <type>
     */
    protected function _getRepositoryUrl($repositoryName) {
        $serverName  = $_SERVER['SERVER_NAME'];
        return  $this->userName.'@'.$serverName.':/gitroot/'.$this->projectName.'/'.$repositoryName.'.git';
    }

    public function _getRepositoryPageUrl($repoId, $repoName) {
        return $this->linkTo($repoName,'/plugins/git/index.php/'.$this->groupId.'/view/'.$repoId.'/');
    }
    
    protected function forkRepositories() {
        $params = $this->getData();
        $this->_getBreadCrumb();
        echo '<h2>'. $this->getText('fork_repositories') .'</h2>';
        if ($this->user->isMember($this->groupId)) {
            echo $this->getText('fork_personal_repositories_desc');
        }
        echo $this->getText('fork_project_repositories_desc');
        if ( !empty($params['repository_list']) ) {
            echo '<form action="" method="POST">';
            echo '<input type="hidden" name="group_id" value="'. (int)$this->groupId .'" />';
            echo '<input type="hidden" name="action" value="fork_repositories_permissions" />';
            $token = new CSRFSynchronizerToken('/plugins/git/?group_id='. (int)$this->groupId .'&action=fork_repositories');
            echo $token->fetchHTMLInput();

            echo '<table id="fork_repositories" cellspacing="0">';
            echo '<thead>';
            echo '<tr valign="top">';
            echo '<td class="first">';
            echo '<label style="font-weight: bold;">'. $this->getText('fork_repositories_select') .'</label>';
            echo '</td>';
            echo '<td>';
            echo '<label style="font-weight: bold;">'. $this->getText('fork_destination_project') .'</label>';
            echo '</td>';
            echo '<td>';
            echo '<label style="font-weight: bold;">'. $this->getText('fork_repositories_path') .'</label>';
            echo '</td>';
            echo '<td class="last">&nbsp;</td>';
            echo '</tr>';
            echo '</thead>';

            echo '<tbody><tr valign="top">';
            echo '<td class="first">';
            $strategy = new GitViewsRepositoriesTraversalStrategy_Selectbox($this);
            echo $strategy->fetch($params['repository_list'], $this->user);
            echo '</td>';

            echo '<td>';
            $options = ' disabled="true" ';
            if ($this->user->isMember($this->groupId)) {
                $options = ' checked="true" ';
            }
            echo '<div>
                <input id="choose_personal" type="radio" name="choose_destination" value="personal" '.$options.' />
                <label for="choose_personal">'.$this->getText('fork_choose_destination_personal').'</label>
            </div>';

            echo $this->fetchCopyToAnotherProject();

            echo '</td>';

            echo '<td>';
            $placeholder = $this->getText('fork_repositories_placeholder');
            echo '<input type="text" title="'. $placeholder .'" placeholder="'. $placeholder .'" id="fork_repositories_path" name="path" />';
            echo '<input type="hidden" id="fork_repositories_prefix" value="u/'. $this->user->getName() .'" />';
            echo '</td>';

            echo '<td class="last">';
            echo '<input type="submit" value="'. $this->getText('fork_repositories') .'" />';
            echo '</td>';

            echo '</tr></tbody></table>';

            echo '</form>';
        }
        echo '<br />';
    }

    /**
     * Creates form to set permissions when fork repositories is performed
     *
     * @return void
     */
    protected function forkRepositoriesPermissions() {
        $purifier = Codendi_HTMLPurifier::instance();
        $pm       = ProjectManager::instance();
        $params   = $this->getData();

        if ($params['scope'] == 'project') {
            $groupId = $params['group_id'];
            $project = $pm->getProject($groupId);
            $destinationHTML = $this->getText('fork_destination_project_message', array($project->getPublicName()));
        } else {
            $groupId = (int)$this->groupId;
            $destinationHTML = $this->getText('fork_destination_personal_message');
        }
        $dao         = new GitDao();
        $request     = new Codendi_Request($params);
        $repoFactory = new GitRepositoryFactory($dao, $pm);

        $sourceReposHTML = '';
        $repositories    = explode(',', $params['repos']);
        foreach ($repositories as $repositoryId) {
            $repository       = $repoFactory->getRepositoryById($repositoryId);
            $sourceReposHTML .= '"'.$purifier->purify($repository->getFullName()).'" ';
        }
        $this->_getBreadCrumb();
        echo '<h2>'.$this->getText('fork_repositories').'</h2>';
        echo $this->getText('fork_repository_message', array($sourceReposHTML));
        echo $destinationHTML;
        echo '<h3>Set permissions for the repository to be created</h3>';
        echo '<form action="" method="POST">';
        echo '<input type="hidden" name="group_id" value="'.(int)$this->groupId.'" />';
        echo '<input type="hidden" name="action" value="do_fork_repositories" />';
        $token = new CSRFSynchronizerToken('/plugins/git/?group_id='.(int)$this->groupId.'&action=fork_repositories');
        echo $token->fetchHTMLInput();
        echo '<input id="fork_repositories_repo" type="hidden" name="repos" value="'.$purifier->purify($params['repos']).'" />';
        echo '<input id="choose_personal" type="hidden" name="choose_destination" value="'.$purifier->purify($params['scope']).'" />';
        echo '<input id="to_project" type="hidden" name="to_project" value="'.$purifier->purify($params['group_id']).'" />';
        echo '<input type="hidden" id="fork_repositories_path" name="path" value="'.$purifier->purify($params['namespace']).'" />';
        echo '<input type="hidden" id="fork_repositories_prefix" value="u/'. $this->user->getName() .'" />';
        if (!empty($repository)) {
            $accessControl = new GitViews_RepoManagement_Pane_AccessControl($repository, $request);
            echo $accessControl->getHeadlessAccessControl($groupId);
        }
        echo '<input type="submit" value="'.$this->getText('fork_repositories').'" />';
        echo '</form>';
    }

    private function fetchCopyToAnotherProject() {
        $html = '';
        $userProjectOptions = $this->getUserProjectsAsOptions($this->user, ProjectManager::instance(), $this->groupId);
        if ($userProjectOptions) {
            $options = ' checked="true" ';
            if ($this->user->isMember($this->groupId)) {
                $options = '';
            }
            $html .= '<div>
                <input id="choose_project" type="radio" name="choose_destination" value="project" '.$options.' />
                <label for="choose_project">'.$this->getText('fork_choose_destination_project').'</label>
            </div>';
            
            $html .= '<select name="to_project" id="fork_destination">';
            $html .= $userProjectOptions;
            $html .= '</select>';
        }
        return $html;
    }
    
    public function getUserProjectsAsOptions(User $user, ProjectManager $manager, $currentProjectId) {
        $purifier   = Codendi_HTMLPurifier::instance();
        $html       = '';
        $option     = '<option value="%d" title="%s">%s</option>';
        $usrProject = array_diff($user->getAllProjects(), array($currentProjectId));
        
        foreach ($usrProject as $projectId) {
            $project = $manager->getProject($projectId);
            if ($user->isMember($projectId, 'A') && $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
                $projectName     = $project->getPublicName();
                $projectUnixName = $purifier->purify($project->getUnixName()); 
                $html           .= sprintf($option, $projectId, $projectUnixName, $projectName);
            }
        }
        return $html;
    }
    
    /**
     * TREE SUBVIEW
     */
    protected function _tree( $params=array() ) {
        if ( empty($params) ) {
            $params = $this->getData();
        }
        if (!empty($params['repository_list']) || (isset($params['repositories_owners']) && $params['repositories_owners']->rowCount() > 0)) {
            //echo '<h3>'.$this->getText('tree_title_available_repo').' <a href="#" onclick="$(\'help_tree\').toggle();"> [?]</a></h3>';
            if (isset($params['repositories_owners']) && $params['repositories_owners']->rowCount() > 0) {
                $current_id = null;
                if (!empty($params['user'])) {
                    $current_id = (int)$params['user'];
                }
                $select = '<select name="user" onchange="this.form.submit()">';
                $uh = UserHelper::instance();
                $selected = 'selected="selected"';
                $select .= '<option value="" '. ($current_id ? '' : $selected) .'>'. $this->getText('tree_title_available_repo') .'</option>';
                foreach ($params['repositories_owners'] as $owner) {
                    $select .= '<option value="'. (int)$owner['repository_creation_user_id'] .'" '. ($owner['repository_creation_user_id'] == $current_id ? $selected : '') .'>'. $uh->getDisplayName($owner['user_name'], $owner['realname']) .'</option>';
                }
                $select .= '</select>';
                echo '<form action="" method="GET">';
                echo '<p>';
                echo '<input type="hidden" name="action" value="index" />';
                echo '<input type="hidden" name="group_id" value="'. (int)$this->groupId .'" />';
                echo $select;
                echo '<noscript><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></noscript>';
                echo '</p>';
                echo '</form>';
            }
            $this->help('tree', array('display' => 'none'));


            $lastPushes = array();
            $dao = new Git_LogDao();
            foreach ($params['repository_list'] as $repository) {
                $id  = $repository['repository_id'];
                $dar = $dao->searchLastPushForRepository($id);
                if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
                    $lastPushes[$id] = $dar->getRow();
                }
            }
            $strategy = new GitViewsRepositoriesTraversalStrategy_Tree($this, $lastPushes);
            echo $strategy->fetch($params['repository_list'], $this->user);
        }
        else {
            echo "<h3>".$this->getText('tree_msg_no_available_repo')."</h3>";
        }
    }
    
    /**
     * Fetch the html code to display the icon of a repository (depends on type of project)
     *
     * @param $access
     * @param $backend_type
     */
    public function fetchAccessType($access, $backendIsGitolite) {
        $accessType = '<span class="plugin_git_repo_privacy" title=';

        if ($backendIsGitolite) {
            //$accessType .= '"'.$this->getText('view_repo_access_custom').'">';
            $accessType .= '"custom">';
            $accessType .= '<img src="'.$this->getController()->getPlugin()->getThemePath().'/images/perms.png" />';
        } else {
            switch ($access) {
                case GitRepository::PRIVATE_ACCESS:
                    $accessType .= '"'.$this->getText('view_repo_access_private').'">';
                    $accessType .= '<img src="'.util_get_image_theme('ic/lock.png').'" />';
                    break;
                case GitRepository::PUBLIC_ACCESS:
                    $accessType .= '"'.$this->getText('view_repo_access_public').'">';
                    $accessType .= '<img src="'.util_get_image_theme('ic/lock-unlock.png').'" />';
                    break;
            }
        }
        $accessType .= '</span>';
        return $accessType;
    }
}

?>