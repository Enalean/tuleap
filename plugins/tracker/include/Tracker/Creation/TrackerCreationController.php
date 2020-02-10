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

declare(strict_types = 1);

namespace Tuleap\Tracker\Creation;

use HTTPRequest;
use Project;
use TrackerManager;
use trackerPlugin;
use Tuleap\Layout\BaseLayout;
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
     * @var TrackerManager
     */
    private $tracker_manager;

    /**
     * @var \UserManager
     */
    private $user_manager;

    /**
     * @var \ProjectManager
     */
    private $project_manager;

    public function __construct(
        TrackerCreationBreadCrumbsBuilder $breadcrumbs_builder,
        \TemplateRendererFactory $renderer_factory,
        TrackerManager $tracker_manager,
        \UserManager $user_manager,
        \ProjectManager $project_manager
    ) {
        $this->breadcrumbs_builder = $breadcrumbs_builder;
        $this->renderer_factory    = $renderer_factory;
        $this->tracker_manager     = $tracker_manager;
        $this->user_manager        = $user_manager;
        $this->project_manager     = $project_manager;
    }

    /**
     * Serves the route /<project_name>/tracker/new
     *
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        $user    = $this->user_manager->getCurrentUser();

        $this->assertANewTrackerCanBeCreated($project, $user);

        $layout->addBreadcrumbs(
            $this->breadcrumbs_builder->build($project, $user)
        );

        $layout->header([
            'title' => dgettext('tuleap-tracker', 'New tracker'),
            'group' => $project->getID(),
            'toptab' => trackerPlugin::SERVICE_SHORTNAME
        ]);

        $templates_dir = __DIR__ . '/../../../templates/tracker-creation';

        $this->renderer_factory->getRenderer($templates_dir)
            ->renderToPage(
                'tracker-creation-app',
                new TrackerCreationPresenter()
            );

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
    public function getProject(array $variables) : Project
    {
        return $this->project_manager->getValidProjectByShortNameOrId($variables['project_name']);
    }

    /**
     * @throws NotFoundException
     * @throws ForbiddenException
     */
    private function assertANewTrackerCanBeCreated(Project $project, \PFUser $user) : void
    {
        if (! $project->usesService(trackerPlugin::SERVICE_SHORTNAME)) {
            throw new NotFoundException(dgettext('tuleap-tracker', 'Tracker service is disabled.'));
        }

        if (! $this->tracker_manager->userCanCreateTracker($project->getID(), $user)) {
            throw new ForbiddenException();
        }
    }
}
