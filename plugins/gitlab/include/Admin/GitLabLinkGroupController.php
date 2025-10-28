<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Admin;

use GitPermissionsManager;
use GitPlugin;
use HTTPRequest;
use Project;
use TemplateRenderer;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\GlobalAdmin\GlobalAdminTabsRenderer;
use Tuleap\Gitlab\Group\CountIntegratedRepositories;
use Tuleap\Gitlab\Group\GitlabServerURIDeducer;
use Tuleap\Gitlab\Group\RetrieveGroupLinkedToProject;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Project\ProjectByUnixNameFactory;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final readonly class GitLabLinkGroupController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    public function __construct(
        private ProjectByUnixNameFactory $project_manager,
        private JavascriptAssetGeneric $wizard_assets,
        private JavascriptAssetGeneric $linked_group_assets,
        private HeaderRenderer $header_renderer,
        private GitPermissionsManager $git_permissions_manager,
        private GlobalAdminTabsRenderer $admin_tabs_renderer,
        private TemplateRenderer $renderer,
        private RetrieveGroupLinkedToProject $group_link_retriever,
        private CountIntegratedRepositories $repositories_counter,
        private GitlabServerURIDeducer $server_uri_deducer,
    ) {
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        if (! $project->usesService(GitPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-git', 'Git service is disabled.'));
        }

        $user = $request->getCurrentUser();
        if (! $this->git_permissions_manager->userIsGitAdmin($user, $project)) {
            throw new ForbiddenException(dgettext('tuleap-git', 'User is not Git administrator.'));
        }

        $group_link = $this->group_link_retriever->retrieveGroupLinkedToProject($project);

        if ($group_link) {
            $number_of_repositories = $this->repositories_counter->countIntegratedRepositories($group_link);
            $presenter              = new LinkedGroupPresenter(
                $project,
                $group_link,
                $this->server_uri_deducer->deduceServerURI($group_link),
                $number_of_repositories,
            );

            $layout->addJavascriptAsset($this->linked_group_assets);
            $this->header_renderer->renderServiceAdministrationHeader($request, $user, $project);
            $this->admin_tabs_renderer->renderTabs($project, GitLabLinkGroupTabPresenter::PANE_NAME);
            $this->renderer->renderToPage('linked-group-information', $presenter);
            $layout->footer([]);
            return;
        }

        $has_group_been_unlinked = $request->get('unlink_group');
        if ($has_group_been_unlinked === '1') {
            $layout->addFeedback(
                \Feedback::SUCCESS,
                dgettext('tuleap-gitlab', 'The GitLab group has been successfully unlinked.')
            );
        }

        $layout->addJavascriptAsset($this->wizard_assets);
        $this->header_renderer->renderServiceAdministrationHeader($request, $user, $project);
        $this->admin_tabs_renderer->renderTabs($project, GitLabLinkGroupTabPresenter::PANE_NAME);
        $this->renderer->renderToPage(
            'link-group-wizard',
            new LinkGroupWizardPresenter($project)
        );
        $layout->footer([]);
    }

    /**
     * @throws NotFoundException
     */
    #[\Override]
    public function getProject(array $variables): Project
    {
        $project = $this->project_manager->getProjectByCaseInsensitiveUnixName($variables['project_name']);
        if (! $project || $project->isError()) {
            throw new NotFoundException(dgettext('tuleap-git', 'Project not found.'));
        }

        return $project;
    }
}
