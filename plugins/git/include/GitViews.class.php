<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\History\GitPhpAccessLogger;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;

require_once 'www/project/admin/permissions.php';

/**
 * GitViews
 */
class GitViews extends PluginViews {

    const DEFAULT_SETTINGS_PANE_ACCESS_CONTROL = 'access_control';
    const DEFAULT_SETTINGS_PANE_MIRRORING      = 'mirroring';

    /** @var Project */
    private $project;

    /** @var GitPermissionsManager */
    private $git_permissions_manager;

    /** @var UGroupManager */
    private $ugroup_manager;

    /** @var Git_GitRepositoryUrlManager */
    private $url_manager;

    /** @var Git_Mirror_MirrorDataMapper */
    private $mirror_data_mapper;

    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;

    /**
     * @var FineGrainedPermissionFactory
     */
    private $fine_grained_permission_factory;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $default_fine_grained_permission_factory;

    /**
     * @var FineGrainedRepresentationBuilder
     */
    private $fine_grained_builder;
    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    /**
     * @var Git_RemoteServer_GerritServerFactory
     */
    private $gerrit_server_factory;

    public function __construct(
        $controller,
        Git_GitRepositoryUrlManager $url_manager,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        GitPermissionsManager $permissions_manager,
        FineGrainedPermissionFactory $fine_grained_permission_factory,
        FineGrainedRetriever $fine_grained_retriever,
        DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        FineGrainedRepresentationBuilder $fine_grained_builder,
        GitPhpAccessLogger $access_loger,
        RegexpFineGrainedRetriever $regexp_retriever,
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory
    ) {
        parent::__construct($controller);
        $this->groupId                                 = (int)$this->request->get('group_id');
        $this->project                                 = ProjectManager::instance()->getProject($this->groupId);
        $this->projectName                             = $this->project->getUnixName();
        $this->userName                                = $this->user->getName();
        $this->git_permissions_manager                 = $permissions_manager;
        $this->ugroup_manager                          = new UGroupManager();
        $this->url_manager                             = $url_manager;
        $this->mirror_data_mapper                      = $mirror_data_mapper;
        $this->fine_grained_permission_factory         = $fine_grained_permission_factory;
        $this->fine_grained_retriever                  = $fine_grained_retriever;
        $this->default_fine_grained_permission_factory = $default_fine_grained_permission_factory;
        $this->fine_grained_builder                    = $fine_grained_builder;
        $this->access_loger                            = $access_loger;
        $this->regexp_retriever                        = $regexp_retriever;
        $this->gerrit_server_factory                   = $gerrit_server_factory;
    }

    public function header() {
        $headers = new \Tuleap\Git\GitViews\GitViewHeader(EventManager::instance(), $this->git_permissions_manager);
        $headers->header($this->request, $this->user, $GLOBALS['HTML'], $this->project);
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
<div id="help_init" class="alert alert-info" style="display:<?php echo $display?>">
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
                        <div id="help_create" class="alert alert-info" style="display:<?php echo $display?>">
                            <h3><?php echo $this->getText('help_create_reference_title'); ?></h3>
                        <?php
                        echo '<ul>'.$this->getText('help_create_reference').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'tree':
                        ?>
                        <div id="help_tree" class="alert alert-info" style="display:<?php echo $display?>">
                        <?php
                        echo '<ul>'.$this->getText('help_tree').'</ul>';
                        ?>
                        </div>
                        <?php
                        break;
                    case 'fork':
                        ?>
                        <div id="help_fork" class="alert alert-info" style="display:<?php echo $display?>">
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
     * REPOSITORY MANAGEMENT VIEW
     */
    public function repoManagement() {
        $params = $this->getData();
        $repository   = $params['repository'];

        echo '<h1>'. $repository->getHTMLLink($this->url_manager) .' - '. $GLOBALS['Language']->getText('global', 'Settings') .'</h1>';
        $repo_management_view = new GitViews_RepoManagement(
            $repository,
            $this->controller->getRequest(),
            $params['driver_factory'],
            $this->gerrit_server_factory->getAvailableServersForProject($this->project),
            $params['gerrit_templates'],
            $this->mirror_data_mapper,
            $params['gerrit_can_migrate_checker'],
            $this->fine_grained_permission_factory,
            $this->fine_grained_retriever,
            $this->fine_grained_builder,
            $this->default_fine_grained_permission_factory,
            $this->git_permissions_manager,
            $this->regexp_retriever
        );
        $repo_management_view->display();
    }

    /**
     * FORK VIEW
     */
    public function fork() {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $initialized  = $repository->isInitialized();

        echo "<h1>". $repository->getHTMLLink($this->url_manager) ."</h1>";
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
                <input type="text" id="repo_name" name="repo_name" value="" /><input type="submit" class="btn btn-default" name="clone" value="<?php echo $this->getText('admin_fork_creation_submit');?>" />
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

        $this->_tree($params);
        if ( $this->getController()->isAPermittedAction('add') ) {
            $this->_createForm();
        }
    }

    /**
     * CREATE REF FORM
     */
    protected function _createForm() {
        $user = UserManager::instance()->getCurrentUser();
        ?>
<h2><?php echo $this->getText('admin_reference_creation_title');
        ?> <a href="#" onclick="$('help_create').toggle();$('help_init').toggle()"><i class="icon-question-sign"></i></a></h2>
<form id="addRepository" action="/plugins/git/?group_id=<?php echo $this->groupId ?>" method="POST" class="form-inline">
    <input type="hidden" id="action" name="action" value="add" />

    <label for="repo_name"><?= $this->getText('admin_reference_creation_input_name'); ?></label>
    <input id="repo_name" name="repo_name" class="" type="text" value=""/>

    <input type="submit" id="repo_add" name="repo_add" value="<?php echo $this->getText('admin_reference_creation_submit')?>" class="btn btn-primary">
</form>
        <?php
        $this->help('create', array('display'=>'none')) ;
        $this->help('init', array('display'=>'none')) ;
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

    protected function forkRepositories() {
        $params = $this->getData();

        echo '<h1>'. $this->getText('fork_repositories') .'</h1>';
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
                <input id="choose_personal" type="radio" name="choose_destination" value="'. Git::SCOPE_PERSONAL .'" '.$options.' />
                <label class="radio" for="choose_personal">'.$this->getText('fork_choose_destination_personal').'</label>
            </div>';

            echo $this->fetchCopyToAnotherProject();

            echo '</td>';

            echo '<td>';
            $placeholder = $this->getText('fork_repositories_placeholder');
            echo '<input type="text" title="'. $placeholder .'" placeholder="'. $placeholder .'" id="fork_repositories_path" name="path" />';
            echo '<input type="hidden" id="fork_repositories_prefix" value="u/'. $this->user->getName() .'" />';
            echo '</td>';

            echo '<td class="last">';
            echo '<input type="submit" class="btn btn-primary" value="'. $this->getText('fork_repositories') .'" />';
            echo '</td>';

            echo '</tr></tbody></table>';

            echo '</form>';
        }
        echo '<br />';
    }

    protected function adminGitAdminsView($are_mirrors_defined) {
        $params = $this->getData();

        $presenter = new GitPresenters_AdminGitAdminsPresenter(
            $this->groupId,
            $are_mirrors_defined,
            $this->ugroup_manager->getStaticUGroups($this->project),
            $this->git_permissions_manager->getCurrentGitAdminUgroups($this->project->getId())
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    protected function adminGerritTemplatesView($are_mirrors_defined) {
        $params = $this->getData();

        $repository_list       = (isset($params['repository_list'])) ? $params['repository_list'] : array();
        $templates_list        = (isset($params['templates_list'])) ? $params['templates_list'] : array();
        $parent_templates_list = (isset($params['parent_templates_list'])) ? $params['parent_templates_list'] : array();

        $presenter = new GitPresenters_AdminGerritTemplatesPresenter(
            $repository_list,
            $templates_list,
            $parent_templates_list,
            $this->groupId,
            $are_mirrors_defined,
            $params['has_gerrit_servers_set_up']
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    protected function adminMassUpdateSelectRepositoriesView() {
        $params = $this->getData();

        $repository_list = $this->getGitRepositoryFactory()->getAllRepositories($this->project);
        $presenter       = new GitPresenters_AdminMassUpdateSelectRepositoriesPresenter(
            $this->generateMassUpdateCSRF(),
            $this->groupId,
            $repository_list
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    protected function adminMassUpdateView() {
        $params = $this->getData();

        $repositories = $params['repositories'];
        $mirrors      = $this->getAdminMassUpdateMirrorPresenters();
        $presenter    = new GitPresenters_AdminMassUpdatePresenter(
            $this->generateMassUpdateCSRF(),
            $this->groupId,
            $this->buildListOfMirroredRepositoriesPresenters(
                $repositories,
                $mirrors,
                $this->mirror_data_mapper->getListOfMirrorIdsPerRepositoryForProject($this->project)
            ),
            new GitPresenters_AdminMassUdpdateMirroringPresenter($mirrors)
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    private function buildListOfMirroredRepositoriesPresenters(
        array $repositories,
        array $mirrors,
        array $mirror_ids_per_repository
    ) {
        $mirrored_repositories_presenters = array();

        foreach ($repositories as $repository) {
            $used_mirrors = array();
            foreach ($mirrors as $mirror) {
                $is_used = isset($mirror_ids_per_repository[$repository->getId()])
                    && in_array($mirror->mirror_id, $mirror_ids_per_repository[$repository->getId()]);

                $copy_of_mirror = clone $mirror;
                $copy_of_mirror->is_used = $is_used;

                $used_mirrors[] = $copy_of_mirror;
            }

            $mirrored_repositories_presenters[] = new GitPresenters_MirroredRepositoryPresenter(
                $repository,
                $used_mirrors
            );
        }

        return $mirrored_repositories_presenters;
    }

    private function generateMassUpdateCSRF() {
        return new CSRFSynchronizerToken('/plugins/git/?group_id='. (int)$this->groupId .'&action=admin-mass-update');
    }

    private function getAdminMassUpdateMirrorPresenters() {
        $mirrors           = $this->mirror_data_mapper->fetchAllForProject($this->project);
        $mirror_presenters = array();

        foreach($mirrors as $mirror) {
            $mirror_presenters[] = new GitPresenters_MirrorPresenter($mirror, false);
        }

        return $mirror_presenters;
    }

    /**
     * Creates form to set permissions when fork repositories is performed
     *
     * @return void
     */
    protected function forkRepositoriesPermissions() {
        $params = $this->getData();

        if ($params['scope'] == 'project') {
            $groupId = $params['group_id'];
        } else {
            $groupId = (int)$this->groupId;
        }

        $repositories = explode(',', $params['repos']);
        $repository   = $this->getGitRepositoryFactory()->getRepositoryById($repositories[0]);
        if (!empty($repository)) {
            $forkPermissionsManager = new GitForkPermissionsManager(
                $repository,
                $this->getAccessRightsPresenterOptionsBuilder(),
                $this->fine_grained_retriever,
                $this->fine_grained_permission_factory,
                $this->fine_grained_builder,
                $this->default_fine_grained_permission_factory,
                $this->git_permissions_manager,
                $this->regexp_retriever
            );

            $userName = $this->user->getName();
            echo $forkPermissionsManager->displayRepositoriesPermissionsForm($params, $groupId, $userName);
        }
    }

    private function getAccessRightsPresenterOptionsBuilder()
    {
        $dao                = new UserGroupDao();
        $user_group_factory = new User_ForgeUserGroupFactory($dao);

        return new AccessRightsPresenterOptionsBuilder($user_group_factory, PermissionsManager::instance());
    }

    private function getGitRepositoryFactory() {
        return new GitRepositoryFactory(new GitDao(), ProjectManager::instance());
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
            <label class="radio">
                <input id="choose_project" type="radio" name="choose_destination" value="project" '.$options.' />
                '.$this->getText('fork_choose_destination_project').'</label>
            </div>';

            $html .= '<select name="to_project" id="fork_destination">';
            $html .= $userProjectOptions;
            $html .= '</select>';
        }
        return $html;
    }

    public function getUserProjectsAsOptions(PFUser $user, ProjectManager $manager, $currentProjectId) {
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
    protected function _tree($params=array()) {
        if ( empty($params) ) {
            $params = $this->getData();
        }
        if (!empty($params['repository_list']) || (isset($params['repositories_owners']) && count($params['repositories_owners']) > 0)) {
            echo '<h1>'.$this->getText('tree_title_available_repo').' <a href="#" onclick="$(\'help_tree\').toggle();"><i class="icon-question-sign"></i></a></h1>';
            if (isset($params['repositories_owners']) && count($params['repositories_owners']) > 0) {
                $purifier   = Codendi_HTMLPurifier::instance();
                $current_id = null;
                if (!empty($params['user'])) {
                    $current_id = (int)$params['user'];
                }
                $select = '<select name="user" onchange="this.form.submit()">';
                $uh = UserHelper::instance();
                $selected = 'selected="selected"';
                $select .= '<option value="" '. ($current_id ? '' : $selected) .'>'. $this->getText('tree_title_available_repo') .'</option>';
                foreach ($params['repositories_owners'] as $owner) {
                    $select .= '<option value="'. (int)$owner['repository_creation_user_id'] .'" '.
                        ($owner['repository_creation_user_id'] == $current_id ? $selected : '') .'>'.
                        $purifier->purify($uh->getDisplayName($owner['user_name'], $owner['realname'])) .
                        '</option>';
                }
                $select .= '</select>';
                echo '<form action="" class="form-tree" method="GET">';
                echo '<input type="hidden" name="action" value="index" />';
                echo '<input type="hidden" name="group_id" value="'. (int)$this->groupId .'" />';
                echo $select;
                echo '<noscript><input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /></noscript>';
                echo '</form>';
            }
            $this->help('tree', array('display' => 'none'));


            $lastPushes = array();
            $dao = new Git_LogDao();
            foreach ($params['repository_list'] as $repository) {
                $id  = $repository['repository_id'];
                $row = $dao->getLastPushForRepository($id);
                if (! empty($row)) {
                    $lastPushes[$id] = $row;
                }
            }
            $strategy = new GitViewsRepositoriesTraversalStrategy_Tree($lastPushes, $this->url_manager);
            echo $strategy->fetch($params['repository_list'], $this->user);
        }
        else {
            echo "<h3>".$this->getText('tree_msg_no_available_repo')."</h3>";
        }
    }

    protected function adminDefaultSettings($are_mirrors_defined, $pane) {
        $mirror_presenters = $this->getMirrorPresentersForGitAdmin();
        $project_id        = $this->project->getID();

        $builder        = $this->getAccessRightsPresenterOptionsBuilder();
        $read_options   = $builder->getDefaultOptions($this->project, Git::DEFAULT_PERM_READ);
        $write_options  = $builder->getDefaultOptions($this->project, Git::DEFAULT_PERM_WRITE);
        $rewind_options = $builder->getDefaultOptions($this->project, Git::DEFAULT_PERM_WPLUS);
        $csrf           = new CSRFSynchronizerToken("plugins/git/?group_id=$project_id&action=admin-default-access-rights");

        $pane_access_control = true;
        $pane_mirroring      = false;

        if ($are_mirrors_defined && $pane === self::DEFAULT_SETTINGS_PANE_MIRRORING) {
            $pane_access_control = false;
            $pane_mirroring      = true;
        }

        $user = UserManager::instance()->getCurrentUser();

        $can_use_fine_grained_permissions = $this->git_permissions_manager->userIsGitAdmin($user, $this->project);

        $are_fine_grained_permissions_defined = $this->fine_grained_retriever->doesProjectUseFineGrainedPermissions(
            $this->project
        );

        $branches_permissions     = $this->default_fine_grained_permission_factory->getBranchesFineGrainedPermissionsForProject($this->project);
        $tags_permissions         = $this->default_fine_grained_permission_factory->getTagsFineGrainedPermissionsForProject($this->project);
        $new_fine_grained_ugroups = $builder->getAllOptions($this->project);

        $delete_url  = '?action=delete-default-permissions&pane=access_control&group_id='.$this->project->getID();
        $url         = '?action=admin-default-settings&pane=access_control&group_id='.$this->project->getID();
        $csrf_delete = new CSRFSynchronizerToken($url);

        $branches_permissions_representation = array();
        foreach ($branches_permissions as $permission) {
            $branches_permissions_representation[] = $this->fine_grained_builder->buildDefaultPermission(
                $permission,
                $this->project
            );
        }

        $tags_permissions_representation = array();
        foreach ($tags_permissions as $permission) {
            $tags_permissions_representation[] = $this->fine_grained_builder->buildDefaultPermission(
                $permission,
                $this->project
            );
        }

        $presenter = new GitPresenters_AdminDefaultSettingsPresenter(
            $project_id,
            $are_mirrors_defined,
            $mirror_presenters,
            $csrf,
            $read_options,
            $write_options,
            $rewind_options,
            $pane_access_control,
            $pane_mirroring,
            $are_fine_grained_permissions_defined,
            $can_use_fine_grained_permissions,
            $branches_permissions_representation,
            $tags_permissions_representation,
            $new_fine_grained_ugroups,
            $delete_url,
            $csrf_delete,
            $this->areRegexpActivatedAtSiteLevel(),
            $this->isRegexpActivatedForDefault(),
            $this->areRegexpConflictingForDefault()
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR).'/templates');

        echo $renderer->renderToString('admin', $presenter);
    }

    private function areRegexpActivatedAtSiteLevel()
    {
        return $this->regexp_retriever->areRegexpActivatedAtSiteLevel();
    }


    private function isRegexpActivatedForDefault()
    {
        return $this->regexp_retriever->areRegexpActivatedForDefault($this->project);
    }

    private function areRegexpConflictingForDefault()
    {
        return $this->regexp_retriever->areDefaultRegexpConflitingWithPlateform($this->project);
    }

    private function getMirrorPresentersForGitAdmin() {
        $mirrors            = $this->mirror_data_mapper->fetchAllForProject($this->project);
        $default_mirror_ids = $this->mirror_data_mapper->getDefaultMirrorIdsForProject($this->project);
        $mirror_presenters  = array();

        foreach ($mirrors as $mirror) {
            $is_used = in_array($mirror->id, $default_mirror_ids);

            $mirror_presenters[] = new GitPresenters_MirrorPresenter($mirror, $is_used);
        }

        return $mirror_presenters;
    }

}
