<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\ProjectOwnership\ProjectAdmin;

use HTTPRequest;
use Project;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerDAO;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;
use UserHelper;
use UserManager;

class IndexController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const PANE_SHORTNAME = 'project_ownership';
    /** @var \TemplateRenderer */
    private $template_renderer;
    /** @var ProjectRetriever */
    private $project_retriever;
    /** @var HeaderNavigationDisplayer */
    private $header_displayer;
    /** @var ProjectOwnerPresenterBuilder */
    private $project_owner_presenter_builder;

    public function __construct(
        \TemplateRenderer $template_renderer,
        ProjectRetriever $project_retriever,
        HeaderNavigationDisplayer $header_displayer,
        ProjectOwnerPresenterBuilder $project_owner_presenter_builder
    ) {
        $this->template_renderer               = $template_renderer;
        $this->project_retriever               = $project_retriever;
        $this->header_displayer                = $header_displayer;
        $this->project_owner_presenter_builder = $project_owner_presenter_builder;
    }

    public static function buildSelf(): self
    {
        return new self(
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates'),
            ProjectRetriever::buildSelf(),
            new HeaderNavigationDisplayer(),
            new ProjectOwnerPresenterBuilder(
                new ProjectOwnerDAO(),
                UserManager::instance(),
                UserHelper::instance(),
                $GLOBALS['Language']
            )
        );
    }

    /**
     * @param array $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project      = $this->project_retriever->getProjectFromId($variables['project_id']);
        $current_user = $request->getCurrentUser();
        $this->checkUserIsProjectAdmin($project, $current_user);

        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../../src/www/assets/project_ownership/themes',
                    '/assets/project_ownership/themes'
                ),
                'project-ownership-project-admin'
            )
        );

        $this->header_displayer->displayBurningParrotNavigation(
            dgettext('tuleap-project_ownership', 'Project ownership'),
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
                dgettext('tuleap-project_ownership', 'You must be project administrator to access this page.')
            );
        }
    }
}
