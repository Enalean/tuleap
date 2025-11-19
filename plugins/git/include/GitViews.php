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

use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\GlobalAdmin\GerritTemplatesPresenter;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Repository\Settings\ArtifactClosure\VerifyArtifactClosureIsAllowed;
use Tuleap\Layout\IncludeViteAssets;

include_once __DIR__ . '/../../../src/www/project/admin/permissions.php';

class GitViews extends PluginViews // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    private readonly Project $project;
    private readonly UGroupManager $ugroup_manager;
    private readonly EventManager $event_manager;
    private int|string $groupId;

    public function __construct(
        $controller,
        private readonly GitPermissionsManager $git_permissions_manager,
        private readonly FineGrainedPermissionFactory $fine_grained_permission_factory,
        private readonly FineGrainedRetriever $fine_grained_retriever,
        private readonly DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        private readonly FineGrainedRepresentationBuilder $fine_grained_builder,
        private readonly RegexpFineGrainedRetriever $regexp_retriever,
        private readonly Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        private readonly HeaderRenderer $header_renderer,
        private readonly ProjectManager $project_manager,
        private readonly VerifyArtifactClosureIsAllowed $closure_verifier,
    ) {
        parent::__construct($controller);
        $this->groupId        = (int) $this->request->get('group_id');
        $this->project        = $this->project_manager->getProject($this->groupId);
        $this->projectName    = $this->project->getUnixName();
        $this->userName       = $this->user->getUserName();
        $this->ugroup_manager = new UGroupManager();
        $this->event_manager  = EventManager::instance();
    }

    public function header()
    {
        $this->header_renderer->renderDefaultHeader($this->request, $this->user, $this->project);
    }

    public function footer(): void
    {
        $GLOBALS['HTML']->footer([]);
    }

    /**
     * REPOSITORY MANAGEMENT VIEW
     */
    public function repoManagement(GitRepository $repository, bool $is_burning_parrot)
    {
        $params               = $this->getData();
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

        $repo_management_view->addPaneAssets();
        $this->header_renderer->renderRepositorySettingsHeader(
            $this->request,
            $this->user,
            $this->project,
            $repository
        );

        echo '<h1 class="project-administration-title">' . Codendi_HTMLPurifier::instance()->purify($repository->getName()) . ' - ' . $GLOBALS['Language']->getText('global', 'Settings') . '</h1>';
        if ($is_burning_parrot) {
            $repo_management_view->display();
        } else {
            $repo_management_view->displayFlamingParrot();
        }

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

    protected function adminGerritTemplatesView(): void
    {
        assert($GLOBALS['HTML'] instanceof \Tuleap\Layout\BaseLayout);
        $GLOBALS['HTML']->addJavascriptAsset(
            new \Tuleap\Layout\JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../scripts/global-admin-gerrit/frontend-assets',
                    '/assets/git/global-admin-gerrit'
                ),
                'src/main.ts'
            )
        );

        $params = $this->getData();

        $repository_list       = $params['repository_list'] ?? [];
        $templates_list        = $params['templates_list'] ?? [];
        $parent_templates_list = $params['parent_templates_list'] ?? [];

        $event = new GitAdminGetExternalPanePresenters($this->project);
        $this->event_manager->processEvent($event);

        $presenter = new GerritTemplatesPresenter(
            $repository_list,
            $templates_list,
            $parent_templates_list,
            (int) $this->groupId,
            $params['has_gerrit_servers_set_up'],
            self::getGerritTemplatesCSRF((int) $this->groupId),
            $event->getExternalPanePresenters(),
        );

        $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../templates');

        $this->header_renderer->renderServiceAdministrationHeader($this->request, $this->user, $this->project);
        $renderer->renderToPage('admin-gerrit-templates', $presenter);
        $this->footer();
    }

    public static function getGerritTemplatesCSRF(int $project_id): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(
            '/plugins/git/?' . http_build_query(
                [
                    'action' => Git::ADMIN_GERRIT_TEMPLATES_ACTION,
                    'group_id' => $project_id,
                ]
            )
        );
    }

    private function generateMassUpdateCSRF()
    {
        return new CSRFSynchronizerToken('/plugins/git/?group_id=' . (int) $this->groupId . '&action=admin-mass-update');
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
