<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Git\AdminGerritBuilder;
use Tuleap\Git\BigObjectAuthorization\BigObjectAuthorizationManager;
use Tuleap\Git\GeneralSettingsController;
use Tuleap\Git\GerritServerResourceRestrictor;
use Tuleap\Git\Permissions\RegexpFineGrainedDisabler;
use Tuleap\Git\Permissions\RegexpFineGrainedEnabler;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\RemoteServer\Gerrit\Restrictor;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;

/**
 * This routes site admin part of Git
 */
class Git_AdminRouter implements \Tuleap\Request\DispatchableWithRequest, \Tuleap\Request\DispatchableWithBurningParrot
{
    /** @var Git_RemoteServer_GerritServerFactory */
    private $gerrit_server_factory;

    /** @var CSRFSynchronizerToken */
    private $csrf;

    /** @var ProjectManager */
    private $project_manager;

    /** @var Git_SystemEventManager */
    private $git_system_event_manager;

    /**
     * @var RegexpFineGrainedRetriever
     */
    private $regexp_retriever;

    /**
     * @var RegexpFineGrainedEnabler
     */
    private $regexp_enabler;
    /**
     * @var RegexpFineGrainedDisabler
     */
    private $regexp_disabler;

    /** @var AdminPageRenderer */
    private $admin_page_renderer;

    /**
     * @var GerritServerResourceRestrictor
     */
    private $gerrit_ressource_restrictor;
    /**
     * @var Restrictor
     */
    private $gerrit_restrictor;

    /**
     * @var BigObjectAuthorizationManager
     */
    private $big_object_authorization_manager;

    private IncludeViteAssets $include_assets;

    public function __construct(
        Git_RemoteServer_GerritServerFactory $gerrit_server_factory,
        CSRFSynchronizerToken $csrf,
        ProjectManager $project_manager,
        Git_SystemEventManager $git_system_event_manager,
        RegexpFineGrainedRetriever $regexp_retriever,
        RegexpFineGrainedEnabler $regexp_enabler,
        AdminPageRenderer $admin_page_renderer,
        RegexpFineGrainedDisabler $regexp_disabler,
        GerritServerResourceRestrictor $gerrit_ressource_restrictor,
        Restrictor $gerrit_restrictor,
        BigObjectAuthorizationManager $big_object_authorization_manager,
        IncludeViteAssets $include_assets,
    ) {
        $this->gerrit_server_factory            = $gerrit_server_factory;
        $this->csrf                             = $csrf;
        $this->project_manager                  = $project_manager;
        $this->git_system_event_manager         = $git_system_event_manager;
        $this->regexp_retriever                 = $regexp_retriever;
        $this->regexp_enabler                   = $regexp_enabler;
        $this->admin_page_renderer              = $admin_page_renderer;
        $this->regexp_disabler                  = $regexp_disabler;
        $this->gerrit_ressource_restrictor      = $gerrit_ressource_restrictor;
        $this->gerrit_restrictor                = $gerrit_restrictor;
        $this->big_object_authorization_manager = $big_object_authorization_manager;
        $this->include_assets                   = $include_assets;
    }

    public function process(HTTPRequest $request, \Tuleap\Layout\BaseLayout $layout, array $variables)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('git');

        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new \Tuleap\Request\ForbiddenException();
        }
        $controller = $this->getControllerFromRequest($request);

        $controller->process($request);

        $layout->addCssAsset(CssViteAsset::fromFileName($this->include_assets, 'themes/git.scss'));

        $controller->display($request);
    }

    private function getControllerFromRequest(Codendi_Request $request)
    {
        if ($request->get('pane') == 'gerrit_servers_admin' || $request->get('view') === 'gerrit_servers_restriction') {
            return new Git_AdminGerritController(
                $this->csrf,
                $this->gerrit_server_factory,
                $this->admin_page_renderer,
                $this->gerrit_ressource_restrictor,
                $this->gerrit_restrictor,
                new AdminGerritBuilder(new User_SSHKeyValidator()),
                new JavascriptViteAsset($this->include_assets, 'src/gerrit/index.ts'),
            );
        } elseif ($request->get('pane') == 'gitolite_config') {
            return new Git_AdminGitoliteConfig(
                $this->csrf,
                $this->project_manager,
                $this->git_system_event_manager,
                $this->admin_page_renderer,
                $this->big_object_authorization_manager,
                new JavascriptViteAsset($this->include_assets, 'src/gitolite.ts'),
            );
        } else {
            return new GeneralSettingsController(
                $this->csrf,
                $this->regexp_retriever,
                $this->regexp_enabler,
                $this->admin_page_renderer,
                $this->regexp_disabler
            );
        }
    }
}
