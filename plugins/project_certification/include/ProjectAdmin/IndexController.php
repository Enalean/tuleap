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

namespace Tuleap\ProjectCertification\ProjectAdmin;

use HTTPRequest;
use Project;
use Project_NotFoundException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class IndexController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    const PANE_SHORTNAME = 'project_certification';
    /** @var \TemplateRenderer */
    private $template_renderer;
    /** @var \ProjectManager */
    private $project_manager;
    /** @var HeaderNavigationDisplayer */
    private $header_displayer;
    /** @var ProjectOwnerPresenterBuilder */
    private $project_owner_presenter_builder;

    public function __construct(
        \TemplateRenderer $template_renderer,
        \ProjectManager $project_manager,
        HeaderNavigationDisplayer $header_displayer,
        ProjectOwnerPresenterBuilder $project_owner_presenter_builder
    ) {
        $this->template_renderer               = $template_renderer;
        $this->project_manager                 = $project_manager;
        $this->header_displayer                = $header_displayer;
        $this->project_owner_presenter_builder = $project_owner_presenter_builder;
    }

    /**
     * @param HTTPRequest $request
     * @param BaseLayout  $layout
     * @param array       $variables
     * @throws NotFoundException
     * @throws ForbiddenException
     * @return void
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project      = $this->getProject($variables['project_id']);
        $current_user = $request->getCurrentUser();
        $this->checkUserIsProjectAdmin($project, $current_user);

        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../../src/www/assets/project_certification/BurningParrot',
                    '/assets/project_certification/BurningParrot'
                ),
                'project-certification-project-admin'
            )
        );

        $this->header_displayer->displayBurningParrotNavigation(
            dgettext('tuleap-project_certification', 'Project certification'),
            $project,
            self::PANE_SHORTNAME
        );
        $this->template_renderer->renderToPage(
            'project-admin',
            $this->project_owner_presenter_builder->build($project)
        );
        project_admin_footer([]);
    }

    /**
     * @throws ForbiddenException
     */
    private function checkUserIsProjectAdmin(Project $project, \PFUser $current_user)
    {
        if (! $current_user->isAdmin($project->getID())) {
            throw new ForbiddenException(
                dgettext('tuleap-project_certification', 'You must be project administrator to access this page.')
            );
        }
    }

    /**
     * @param int $project_id
     *
     * @return Project
     * @throws NotFoundException
     */
    public function getProject($project_id)
    {
        try {
            $project = $this->project_manager->getValidProject($project_id);
        } catch (Project_NotFoundException $e) {
            throw new NotFoundException(dgettext('tuleap-project_certification', 'Project does not exist.'), $e);
        }

        return $project;
    }
}
