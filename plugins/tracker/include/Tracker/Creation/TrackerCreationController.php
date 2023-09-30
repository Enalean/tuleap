<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation;

use CSRFSynchronizerToken;
use HTTPRequest;
use Project;
use trackerPlugin;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class TrackerCreationController implements DispatchableWithRequest, DispatchableWithProject, DispatchableWithBurningParrot
{
    /**
     * @var TrackerCreationBreadCrumbsBuilder
     */
    private $breadcrumbs_builder;

    /**
     * @var \TemplateRendererFactory
     */
    private $renderer_factory;

    /**
     * @var \UserManager
     */
    private $user_manager;

    /**
     * @var \ProjectManager
     */
    private $project_manager;
    /**
     * @var TrackerCreationPresenterBuilder
     */
    private $presenter_builder;

    /**
     * @var TrackerCreationPermissionChecker
     */
    private $permission_checker;

    public function __construct(
        TrackerCreationBreadCrumbsBuilder $breadcrumbs_builder,
        \TemplateRendererFactory $renderer_factory,
        \UserManager $user_manager,
        \ProjectManager $project_manager,
        TrackerCreationPresenterBuilder $presenter_builder,
        TrackerCreationPermissionChecker $permission_checker,
    ) {
        $this->breadcrumbs_builder = $breadcrumbs_builder;
        $this->renderer_factory    = $renderer_factory;
        $this->user_manager        = $user_manager;
        $this->project_manager     = $project_manager;
        $this->presenter_builder   = $presenter_builder;
        $this->permission_checker  = $permission_checker;
    }

    /**
     * Serves the route /plugins/tracker/<project_name>/new
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        $user    = $this->user_manager->getCurrentUser();

        $this->permission_checker->checkANewTrackerCanBeCreated($project, $user);

        $layout->addBreadcrumbs(
            $this->breadcrumbs_builder->build($project, $user)
        );

        $assets = new IncludeAssets(
            __DIR__ . '/../../../scripts/tracker-creation/frontend-assets',
            '/assets/trackers/tracker-creation'
        );
        $layout->addCssAsset(new CssAssetWithoutVariantDeclinaisons($assets, 'tracker-creation-style'));

        $layout->header(
            HeaderConfigurationBuilder::get(dgettext('tuleap-tracker', 'New tracker'))
                ->inProject($project, trackerPlugin::SERVICE_SHORTNAME)
                ->build(),
        );

        $templates_dir = __DIR__ . '/../../../templates/tracker-creation';

        $this->renderer_factory->getRenderer($templates_dir)
            ->renderToPage(
                'tracker-creation-app',
                $this->presenter_builder->build(
                    $project,
                    $this->getCSRFTokenForSubmission($project),
                    $user
                )
            );

        $layout->addJavascriptAsset(new JavascriptAsset($assets, 'tracker-creation.js'));
        $layout->footer([]);
    }

    /**
     * Return the project that corresponds to current URI
     *
     * This part of controller is needed when you implement a new route without providing a $group_id.
     * It's the preferred way to deal with those kind of URLs over Event::GET_PROJECTID_FROM_URL
     *
     * @param array $variables
     */
    public function getProject(array $variables): Project
    {
        return $this->project_manager->getValidProjectByShortNameOrId($variables['project_name']);
    }

    private function getCSRFTokenForSubmission(Project $project): CSRFSynchronizerToken
    {
        return new CSRFSynchronizerToken(
            TrackerCreationProcessorController::getRouteToSubmissionController($project)
        );
    }

    public static function getRouteToTrackerCreationController(Project $project): string
    {
        return '/plugins/tracker/' . urlencode($project->getUnixNameLowerCase()) . '/new';
    }
}
