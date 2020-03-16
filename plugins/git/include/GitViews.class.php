<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\History\GitPhpAccessLogger;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;

include_once __DIR__ . '/../../../src/www/project/admin/permissions.php';

/**
 * GitViews
 */
class GitViews extends PluginViews
{

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
    /**
     * @var HeaderRenderer
     */
    private $header_renderer;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var ProjectManager
     */
    private $project_manager;

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
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        HeaderRenderer $header_renderer,
        ProjectManager $project_manager
    ) {
        parent::__construct($controller);
        $this->groupId                                 = (int) $this->request->get('group_id');
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
        $this->event_manager                           = EventManager::instance();
        $this->header_renderer                         = $header_renderer;
        $this->project_manager                         = $project_manager;
    }

    public function header()
    {
        $this->header_renderer->renderDefaultHeader($this->request, $this->user, $this->project);
    }

    public function footer()
    {
        $GLOBALS['HTML']->footer(array());
    }

    /**
     * REPOSITORY MANAGEMENT VIEW
     */
    public function repoManagement()
    {
        $params = $this->getData();
        $repository   = $params['repository'];

        $this->header_renderer->renderRepositorySettingsHeader(
            $this->request,
            $this->user,
            $this->project,
            $repository
        );

        echo '<h1>' . $repository->getHTMLLink($this->url_manager) . ' - ' . $GLOBALS['Language']->getText('global', 'Settings') . '</h1>';
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
            $this->regexp_retriever,
            $this->event_manager,
            $this->project_manager
        );
        $repo_management_view->display();

        $this->footer();
    }

    /**
     * CONFIRM PRIVATE
     */
    public function confirmPrivate()
    {
        $params = $this->getData();
        $repository   = $params['repository'];
        $repoId       = $repository->getId();
        $repoName     = $repository->getName();
        $initialized  = $repository->isInitialized();
        $mails        = $params['mails'];
        if ($this->getController()->isAPermittedAction('save')) :
            ?>
        <div class="confirm">
        <h3><?php echo dgettext('tuleap-git', 'Do you confirm change of repository access to private ?'); ?></h3>
        <form id="confirm_private" method="POST" action="/plugins/git/?group_id=<?php echo $this->groupId; ?>" >
        <input type="hidden" id="action" name="action" value="set_private" />
        <input type="hidden" id="repo_id" name="repo_id" value="<?php echo $repoId; ?>" />
        <input type="submit" id="submit" name="submit" value="<?php echo dgettext('tuleap-git', 'Yes') ?>"/><span><input type="button" value="<?php echo dgettext('tuleap-git', 'No')?>" onclick="window.location='/plugins/git/?action=view&group_id=<?php echo $this->groupId;?>&repo_id=<?php echo $repoId?>'"/> </span>
        </form>
        <h3><?php echo dgettext('tuleap-git', 'List of mails to remove from notification'); ?></h3>
    <table>
            <?php
            $i = 0;
            foreach ($mails as $mail) {
                echo '<tr class="' . html_get_alt_row_color(++$i) . '">';
                echo '<td>' . $mail . '</td>';
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
    public function index()
    {
        $params = $this->getData();

        $this->_tree($params);
        if ($this->getController()->isAPermittedAction('add')) {
            $this->_createForm();
        }
    }

    protected function forkRepositories()
    {
        $params = $this->getData();

        echo '<h1>' . dgettext('tuleap-git', 'Fork repositories') . '</h1>';
        if ($this->user->isMember($this->groupId)) {
            echo dgettext('tuleap-git', '<p>You can create personal forks of any reference repositories. By default forks will end up into your personal area of this project.</p></p>');
        }
        echo dgettext('tuleap-git', '<p>You might choose to fork into another project. In this case, fork creates new "References" in the target project.<br />You need to be administrator of the target project to do so and Git service must be activated.</p>');
        if (!empty($params['repository_list'])) {
            echo '<form action="" method="POST">';
            echo '<input type="hidden" name="group_id" value="' . (int) $this->groupId . '" />';
            echo '<input type="hidden" name="action" value="fork_repositories_permissions" />';
            $token = new CSRFSynchronizerToken('/plugins/git/?group_id=' . (int) $this->groupId . '&action=fork_repositories');
            echo $token->fetchHTMLInput();

            echo '<table id="fork_repositories" cellspacing="0">';
            echo '<thead>';
            echo '<tr valign="top">';
            echo '<td class="first">';
            echo '<label style="font-weight: bold;">' . dgettext('tuleap-git', 'Select repositories to fork') . '</label>';
            echo '</td>';
            echo '<td>';
            echo '<label style="font-weight: bold;">' . dgettext('tuleap-git', 'Choose a destination project') . '</label>';
            echo '</td>';
            echo '<td>';
            echo '<label style="font-weight: bold;">' . dgettext('tuleap-git', 'Choose the path for the forks') . '</label>';
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
                <input id="choose_personal" type="radio" name="choose_destination" value="' . Git::SCOPE_PERSONAL . '" ' . $options . ' />
                <label class="radio" for="choose_personal">' . dgettext('tuleap-git', 'Create personal repositories in this project') . '</label>
            </div>';

            echo $this->fetchCopyToAnotherProject();

            echo '</td>';

            echo '<td>';
            $placeholder = dgettext('tuleap-git', 'Enter a path or leave it blank');
            echo '<input type="text" title="' . $placeholder . '" placeholder="' . $placeholder . '" id="fork_repositories_path" name="path" />';
            echo '<input type="hidden" id="fork_repositories_prefix" value="u/' . $this->user->getName() . '" />';
            echo '</td>';

            echo '<td class="last">';
            echo '<input type="submit" class="btn btn-primary" value="' . dgettext('tuleap-git', 'Fork repositories') . '" />';
            echo '</td>';

            echo '</tr></tbody></table>';

            echo '</form>';
        }
        echo '<br />';
    }

    protected function adminGitAdminsView($are_mirrors_defined)
    {
        $event = new GitAdminGetExternalPanePresenters($this->project);
        $this->event_manager->processEvent($event);

        $presenter = new GitPresenters_AdminGitAdminsPresenter(
            $this->groupId,
            $are_mirrors_defined,
            $event->getExternalPanePresenters(),
            $this->ugroup_manager->getStaticUGroups($this->project),
            $this->git_permissions_manager->getCurrentGitAdminUgroups($this->project->getId())
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        $this->header_renderer->renderServiceAdministrationHeader($this->request, $this->user, $this->project);
        echo $renderer->renderToString('admin-git-admins', $presenter);
        $this->footer();
    }

    protected function adminGerritTemplatesView($are_mirrors_defined)
    {
        $params = $this->getData();

        $repository_list       = (isset($params['repository_list'])) ? $params['repository_list'] : array();
        $templates_list        = (isset($params['templates_list'])) ? $params['templates_list'] : array();
        $parent_templates_list = (isset($params['parent_templates_list'])) ? $params['parent_templates_list'] : array();

        $event = new GitAdminGetExternalPanePresenters($this->project);
        $this->event_manager->processEvent($event);

        $presenter = new GitPresenters_AdminGerritTemplatesPresenter(
            $repository_list,
            $templates_list,
            $parent_templates_list,
            $this->groupId,
            $are_mirrors_defined,
            $event->getExternalPanePresenters(),
            $params['has_gerrit_servers_set_up']
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        $this->header_renderer->renderServiceAdministrationHeader($this->request, $this->user, $this->project);
        echo $renderer->renderToString('admin-gerrit-templates', $presenter);
        $this->footer();
    }

    protected function adminMassUpdateSelectRepositoriesView()
    {
        $repository_list = $this->getGitRepositoryFactory()->getAllRepositories($this->project);

        $event = new GitAdminGetExternalPanePresenters($this->project);
        $this->event_manager->processEvent($event);

        $presenter = new GitPresenters_AdminMassUpdateSelectRepositoriesPresenter(
            $this->generateMassUpdateCSRF(),
            $this->groupId,
            $event->getExternalPanePresenters(),
            $repository_list
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        $this->header_renderer->renderServiceAdministrationHeader($this->request, $this->user, $this->project);
        echo $renderer->renderToString('admin-mass-update-select-repositories', $presenter);
        $this->footer();
    }

    protected function adminMassUpdateView()
    {
        $params = $this->getData();

        $repositories = $params['repositories'];
        $mirrors      = $this->getAdminMassUpdateMirrorPresenters();

        $event = new GitAdminGetExternalPanePresenters($this->project);
        $this->event_manager->processEvent($event);

        $presenter = new GitPresenters_AdminMassUpdatePresenter(
            $this->generateMassUpdateCSRF(),
            $this->groupId,
            $event->getExternalPanePresenters(),
            $this->buildListOfMirroredRepositoriesPresenters(
                $repositories,
                $mirrors,
                $this->mirror_data_mapper->getListOfMirrorIdsPerRepositoryForProject($this->project)
            ),
            new GitPresenters_AdminMassUdpdateMirroringPresenter($mirrors)
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        $this->header_renderer->renderServiceAdministrationHeader($this->request, $this->user, $this->project);
        echo $renderer->renderToString('admin-mass-update', $presenter);
        $this->footer();
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

    private function generateMassUpdateCSRF()
    {
        return new CSRFSynchronizerToken('/plugins/git/?group_id=' . (int) $this->groupId . '&action=admin-mass-update');
    }

    private function getAdminMassUpdateMirrorPresenters()
    {
        $mirrors           = $this->mirror_data_mapper->fetchAllForProject($this->project);
        $mirror_presenters = array();

        foreach ($mirrors as $mirror) {
            $mirror_presenters[] = new GitPresenters_MirrorPresenter($mirror, false);
        }

        return $mirror_presenters;
    }

    /**
     * Creates form to set permissions when fork repositories is performed
     *
     * @return void
     */
    protected function forkRepositoriesPermissions()
    {
        $params = $this->getData();

        if ($params['scope'] == 'project') {
            $groupId = $params['group_id'];
        } else {
            $groupId = (int) $this->groupId;
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

    private function getGitRepositoryFactory()
    {
        return new GitRepositoryFactory(new GitDao(), ProjectManager::instance());
    }

    private function fetchCopyToAnotherProject()
    {
        $html = '';
        $userProjectOptions = $this->getUserProjectsAsOptions($this->user, ProjectManager::instance(), $this->groupId);
        if ($userProjectOptions) {
            $options = ' checked="true" ';
            if ($this->user->isMember($this->groupId)) {
                $options = '';
            }
            $html .= '<div>
            <label class="radio">
                <input id="choose_project" type="radio" name="choose_destination" value="project" ' . $options . ' />
                ' . dgettext('tuleap-git', 'Copy to another project') . '</label>
            </div>';

            $html .= '<select name="to_project" id="fork_destination">';
            $html .= $userProjectOptions;
            $html .= '</select>';
        }
        return $html;
    }

    public function getUserProjectsAsOptions(PFUser $user, ProjectManager $manager, $currentProjectId)
    {
        $purifier   = Codendi_HTMLPurifier::instance();
        $html       = '';
        $option     = '<option value="%d" title="%s">%s</option>';
        $usrProject = array_diff($user->getAllProjects(), array($currentProjectId));

        foreach ($usrProject as $projectId) {
            $project = $manager->getProject($projectId);
            if ($user->isMember($projectId, 'A') && $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
                $projectName     = $purifier->purify($project->getPublicName());
                $projectUnixName = $purifier->purify($project->getUnixName());
                $html           .= sprintf($option, $projectId, $projectUnixName, $projectName);
            }
        }
        return $html;
    }
}
