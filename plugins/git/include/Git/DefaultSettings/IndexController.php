<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\DefaultSettings;

use EventManager;
use Git_Mirror_MirrorDataMapper;
use GitPermissionsManager;
use GitPresenters_AdminDefaultSettingsPresenter;
use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Git\AccessRightsPresenterOptionsBuilder;
use Tuleap\Git\DefaultSettings\Pane\AccessControl;
use Tuleap\Git\DefaultSettings\Pane\DefaultSettingsPanesCollection;
use Tuleap\Git\DefaultSettings\Pane\DisabledPane;
use Tuleap\Git\DefaultSettings\Pane\Mirroring;
use Tuleap\Git\Events\GitAdminGetExternalPanePresenters;
use Tuleap\Git\GitViews\Header\HeaderRenderer;
use Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRepresentationBuilder;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use UserManager;

class IndexController
{
    /**
     * @var AccessRightsPresenterOptionsBuilder
     */
    private $access_rights_builder;
    /**
     * @var GitPermissionsManager
     */
    private $git_permissions_manager;
    /**
     * @var FineGrainedRetriever
     */
    private $fine_grained_retriever;
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
     * @var Git_Mirror_MirrorDataMapper
     */
    private $mirror_data_mapper;
    /**
     * @var HeaderRenderer
     */
    private $header_renderer;
    /**
     * @var EventManager
     */
    private $event_manager;

    public function __construct(
        AccessRightsPresenterOptionsBuilder $access_rights_builder,
        GitPermissionsManager $git_permissions_manager,
        FineGrainedRetriever $fine_grained_retriever,
        DefaultFineGrainedPermissionFactory $default_fine_grained_permission_factory,
        FineGrainedRepresentationBuilder $fine_grained_builder,
        RegexpFineGrainedRetriever $regexp_retriever,
        Git_Mirror_MirrorDataMapper $mirror_data_mapper,
        HeaderRenderer $header_renderer,
        EventManager $event_manager
    ) {
        $this->access_rights_builder                   = $access_rights_builder;
        $this->git_permissions_manager                 = $git_permissions_manager;
        $this->fine_grained_retriever                  = $fine_grained_retriever;
        $this->default_fine_grained_permission_factory = $default_fine_grained_permission_factory;
        $this->fine_grained_builder                    = $fine_grained_builder;
        $this->regexp_retriever                        = $regexp_retriever;
        $this->mirror_data_mapper                      = $mirror_data_mapper;
        $this->header_renderer                         = $header_renderer;
        $this->event_manager                           = $event_manager;
    }

    public function displayDefaultSettings(HTTPRequest $request)
    {
        $project = $request->getProject();

        $are_mirrors_defined = $this->areMirrorsEnabledForProject($project);

        $panes = $this->getPanes($project, $request, $are_mirrors_defined);

        $event = new GitAdminGetExternalPanePresenters($project);
        $this->event_manager->processEvent($event);

        $presenter = new GitPresenters_AdminDefaultSettingsPresenter(
            $project->getID(),
            $are_mirrors_defined,
            $event->getExternalPanePresenters(),
            $panes
        );

        $this->render($request, $presenter);
    }

    private function areMirrorsEnabledForProject(Project $project)
    {
        return count($this->mirror_data_mapper->fetchAllForProject($project)) > 0;
    }

    /**
     * @param bool        $are_mirrors_defined
     *
     * @return Pane\Pane[]
     */
    private function getPanes(Project $project, HTTPRequest $request, $are_mirrors_defined)
    {
        $current_pane   = AccessControl::NAME;
        $requested_pane = $request->get('pane');
        if ($requested_pane) {
            $current_pane = $requested_pane;
        }

        $panes = new DefaultSettingsPanesCollection($project, $current_pane);

        $panes->add(new DisabledPane(dgettext('tuleap-git', 'General settings')));
        $panes->add(
            new AccessControl(
                $this->access_rights_builder,
                $this->git_permissions_manager,
                $this->fine_grained_retriever,
                $this->default_fine_grained_permission_factory,
                $this->fine_grained_builder,
                $this->regexp_retriever,
                UserManager::instance(),
                $project,
                $current_pane === AccessControl::NAME
            )
        );
        $panes->add(new DisabledPane(dgettext('tuleap-git', 'CI Token')));

        if ($are_mirrors_defined) {
            $panes->add(new Mirroring($this->mirror_data_mapper, $project, $current_pane === Mirroring::NAME));
        }

        $panes->add(new DisabledPane(dgettext('tuleap-git', 'Notifications')));
        $panes->add(new DisabledPane(dgettext('tuleap-git', 'Webhooks')));

        $this->event_manager->processEvent($panes);

        return $panes->getPanes();
    }

    /**
     * @param             $presenter
     */
    private function render(HTTPRequest $request, $presenter)
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(dirname(GIT_BASE_DIR) . '/templates');

        $this->header_renderer->renderServiceAdministrationHeader(
            $request,
            $request->getCurrentUser(),
            $request->getProject()
        );
        $renderer->renderToPage('admin-default-settings', $presenter);
        $GLOBALS['HTML']->footer([]);
    }
}
