<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;

include_once __DIR__ . '/../../../src/www/project/admin/permissions.php';

/**
 * GitViews
 */
class GitViews extends PluginViews // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private Project $project;
    private UGroupManager $ugroup_manager;
    private EventManager $event_manager;

    public function __construct(
        $controller,
        private GitPermissionsManager $git_permissions_manager,
        private FineGrainedPermissionFactory $fine_grained_permission_factory,
        private FineGrainedRetriever $fine_grained_retriever,
        private DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        private FineGrainedRepresentationBuilder $fine_grained_builder,
        private RegexpFineGrainedRetriever $regexp_retriever,
        private Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        private HeaderRenderer $header_renderer,
        private ProjectManager $project_manager,
        private VerifyArtifactClosureIsAllowed $closure_verifier,
    ) {
        parent::__construct($controller);
        $this->groupId        = (int) $this->request->get('group_id');
        $this->project        = ProjectManager::instance()->getProject($this->groupId);
        $this->projectName    = $this->project->getUnixName();
        $this->userName       = $this->user->getUserName();
        $this->ugroup_manager = new UGroupManager();
        $this->event_manager  = EventManager::instance();
    }

    public function header()
    {
        $this->header_renderer->renderDefaultHeader($this->request, $this->user, $this->project);
    }

    public function footer()
    {
        $GLOBALS['HTML']->footer([]);
    }

    /**
     * REPOSITORY MANAGEMENT VIEW
     */
    public function repoManagement()
    {
        $params     = $this->getData();
        $repository = $params['repository'];

        $this->header_renderer->renderRepositorySettingsHeader(
            $this->request,
            $this->user,
            $this->project,
            $repository
        );

        echo '<h1 class="almost-tlp-title administration-title">' . Codendi_HTMLPurifier::instance()->purify($repository->getName()) . ' - ' . $GLOBALS['Language']->getText('global', 'Settings') . '</h1>';
        $repo_management_view = new GitViews_RepoManagement(
            $repository,
            $this->controller->getRequest(),
            $params['driver_factory'],
            $this->gerrit_server_factory->getAvailableServersForProject($this->project),
            $params['gerrit_templates'],
            $params['gerrit_can_migrate_checker'],
            $this->fine_grained_permission_factory,
            $this->fine_grained_retriever,
            $this->fine_grained_builder,
            $this->default_fine_grained_permission_factory,
            $this->git_permissions_manager,
            $this->regexp_retriever,
            $this->event_manager,
            $this->project_manager,
            $this->closure_verifier,
        );
        $repo_management_view->display();

        $this->footer();
    }

    /**
     * CONFIRM PRIVATE
     */
    public function confirmPrivate()
    {
        $params      = $this->getData();
        $repository  = $params['repository'];
        $repoId      = $repository->getId();
        $repoName    = $repository->getName();
        $initialized = $repository->isInitialized();
        $mails       = $params['mails'];
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

        echo '<h1 class="almost-tlp-title administration-title">' . dgettext('tuleap-git', 'Fork repositories') . '</h1>';
        echo '<div class="git-fork-creation-content">';
        if ($this->user->isMember($this->groupId)) {
            echo dgettext('tuleap-git', '<p>You can create personal forks of any reference repositories. By default forks will end up into your personal area of this project.</p></p>');
        }
        echo dgettext('tuleap-git', '<p>You might choose to fork into another project. In this case, fork creates new "References" in the target project.<br />You need to be administrator of the target project to do so and Git service must be activated.</p>');
        if (! empty($params['repository_list'])) {
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
                <input id="choose_personal" type="radio" name="choose_destination" value="' . Git::SCOPE_PERSONAL . '" ' . $options . ' data-test="in-this-project"/>
                <label class="radio" for="choose_personal">' . dgettext('tuleap-git', 'Create personal repositories in this project') . '</label>
            </div>';

            echo $this->fetchCopyToAnotherProject();

            echo '</td>';

            $purifier = Codendi_HTMLPurifier::instance();

            echo '<td>';
            $placeholder = dgettext('tuleap-git', 'Enter a path or leave it blank');
            echo '<input type="text" title="' . $placeholder . '" placeholder="' . $placeholder . '" data-test="fork-repository-path" id="fork_repositories_path" name="path" />';
            echo '<input type="hidden" id="fork_repositories_prefix" value="u/' . $purifier->purify($this->user->getUserName()) . '" />';
            echo '</td>';

            echo '<td class="last">';
            echo '<input type="submit" class="btn btn-primary" value="' . dgettext('tuleap-git', 'Fork repositories') . '" data-test="create-fork-button" />';
            echo '</td>';

            echo '</tr></tbody></table>';

            echo '</form>';
        }
        echo '</div>';
    }

    protected function adminGitAdminsView()
    {
        $event = new GitAdminGetExternalPanePresenters($this->project);
        $this->event_manager->processEvent($event);

        $presenter = new GitPresenters_AdminGitAdminsPresenter(
            $this->groupId,
            $event->getExternalPanePresenters(),
            $this->ugroup_manager->getStaticUGroups($this->project),
            $this->git_permissions_manager->getCurrentGitAdminUgroups($this->project->getId())
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        $this->header_renderer->renderServiceAdministrationHeader($this->request, $this->user, $this->project);
        echo $renderer->renderToString('admin-git-admins', $presenter);
        $this->footer();
    }

    protected function adminGerritTemplatesView()
    {
        $params = $this->getData();

        $repository_list       = (isset($params['repository_list'])) ? $params['repository_list'] : [];
        $templates_list        = (isset($params['templates_list'])) ? $params['templates_list'] : [];
        $parent_templates_list = (isset($params['parent_templates_list'])) ? $params['parent_templates_list'] : [];

        $event = new GitAdminGetExternalPanePresenters($this->project);
        $this->event_manager->processEvent($event);

        $presenter = new GitPresenters_AdminGerritTemplatesPresenter(
            $repository_list,
            $templates_list,
            $parent_templates_list,
            $this->groupId,
            $event->getExternalPanePresenters(),
            $params['has_gerrit_servers_set_up']
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        $this->header_renderer->renderServiceAdministrationHeader($this->request, $this->user, $this->project);
        echo $renderer->renderToString('admin-gerrit-templates', $presenter);
        $this->footer();
    }

    private function generateMassUpdateCSRF()
    {
        return new CSRFSynchronizerToken('/plugins/git/?group_id=' . (int) $this->groupId . '&action=admin-mass-update');
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
        if (! empty($repository)) {
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

            $userName = $this->user->getUserName();
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
        $html               = '';
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

            $html .= '<select name="to_project" id="fork_destination" data-test="fork-destination-project">';
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
        $usrProject = array_diff($user->getAllProjects(), [$currentProjectId]);

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
