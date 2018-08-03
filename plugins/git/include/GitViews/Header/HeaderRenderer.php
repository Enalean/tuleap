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

namespace Tuleap\Git\GitViews\Header;

use EventManager;
use GitRepository;
use HTTPRequest;
use PFUser;
use Project;
use Tuleap\Git\BreadCrumbDropdown\GitCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\RepositoryCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\RepositorySettingsCrumbBuilder;
use Tuleap\Git\BreadCrumbDropdown\ServiceAdministrationCrumbBuilder;
use Tuleap\Git\GitViews\GitViewHeader;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

class HeaderRenderer
{
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var GitCrumbBuilder
     */
    private $service_crumb_builder;
    /**
     * @var RepositorySettingsCrumbBuilder
     */
    private $settings_crumbs_builder;
    /**
     * @var ServiceAdministrationCrumbBuilder
     */
    private $administration_crumb_builder;
    /**
     * @var RepositoryCrumbBuilder
     */
    private $repository_crumb_builder;

    public function __construct(
        EventManager $event_manager,
        GitCrumbBuilder $service_crumb_builder,
        ServiceAdministrationCrumbBuilder $administration_crumb_builder,
        RepositoryCrumbBuilder $repository_crumb_builder,
        RepositorySettingsCrumbBuilder $settings_crumbs_builder
    ) {
        $this->event_manager                = $event_manager;
        $this->service_crumb_builder        = $service_crumb_builder;
        $this->settings_crumbs_builder      = $settings_crumbs_builder;
        $this->administration_crumb_builder = $administration_crumb_builder;
        $this->repository_crumb_builder     = $repository_crumb_builder;
    }

    public function renderDefaultHeader(HTTPRequest $request, PFUser $user, Project $project)
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->service_crumb_builder->build(
                $user,
                $project
            )
        );

        $this->renderHeader($request, $project, $breadcrumbs);
    }

    public function renderServiceAdministrationHeader(HTTPRequest $request, PFUser $user, Project $project)
    {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->service_crumb_builder->build(
                $user,
                $project
            )
        );
        $breadcrumbs->addBreadCrumb($this->administration_crumb_builder->build($project));

        $this->renderHeader($request, $project, $breadcrumbs);
    }

    public function renderRepositoryHeader(
        HTTPRequest $request,
        PFUser $user,
        Project $project,
        GitRepository $repository
    ) {
        $breadcrumbs = $this->getRepositoryBreadCrumbs($user, $project, $repository);
        $this->renderHeader($request, $project, $breadcrumbs);
    }

    public function renderRepositorySettingsHeader(
        HTTPRequest $request,
        PFUser $user,
        Project $project,
        GitRepository $repository
    ) {
        $breadcrumbs = $this->getRepositoryBreadCrumbs($user, $project, $repository);
        $breadcrumbs->addBreadCrumb($this->settings_crumbs_builder->build($repository));

        $this->renderHeader($request, $project, $breadcrumbs);
    }

    private function getRepositoryBreadCrumbs(
        PFUser $user,
        Project $project,
        GitRepository $repository
    ) {
        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb(
            $this->service_crumb_builder->build(
                $user,
                $project
            )
        );
        $breadcrumbs->addBreadCrumb($this->repository_crumb_builder->build($user, $repository));
        return $breadcrumbs;
    }

    private function renderHeader(
        HTTPRequest $request,
        Project $project,
        BreadCrumbCollection $breadcrumbs
    ) {
        $headers = new GitViewHeader(
            $this->event_manager,
            $this->service_crumb_builder
        );
        $headers->header($request, $GLOBALS['HTML'], $project, $breadcrumbs);
    }
}
