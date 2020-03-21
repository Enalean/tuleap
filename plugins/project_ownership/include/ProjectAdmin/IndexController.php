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
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Routing\AdministrationLayoutHelper;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\ProjectOwnership\ProjectOwner\ProjectOwnerDAO;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use UserHelper;
use UserManager;

class IndexController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const PANE_SHORTNAME = 'project_ownership';

    /** @var LayoutHelper */
    private $layout_helper;
    /** @var \TemplateRenderer */
    private $template_renderer;
    /** @var ProjectOwnerPresenterBuilder */
    private $project_owner_presenter_builder;

    public function __construct(
        LayoutHelper $layout_helper,
        \TemplateRenderer $template_renderer,
        ProjectOwnerPresenterBuilder $project_owner_presenter_builder
    ) {
        $this->layout_helper                   = $layout_helper;
        $this->template_renderer               = $template_renderer;
        $this->project_owner_presenter_builder = $project_owner_presenter_builder;
    }

    public static function buildSelf(): self
    {
        return new self(
            AdministrationLayoutHelper::buildSelf(),
            TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates'),
            new ProjectOwnerPresenterBuilder(
                new ProjectOwnerDAO(),
                UserManager::instance(),
                UserHelper::instance(),
                $GLOBALS['Language']
            )
        );
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../../src/www/assets/project_ownership',
                    '/assets/project_ownership'
                ),
                'project-ownership-project-admin'
            )
        );
        $callback = function (\Project $project, \PFUser $current_user): void {
            $this->template_renderer->renderToPage(
                'project-admin',
                $this->project_owner_presenter_builder->build($project)
            );
        };
        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $variables['project_id'],
            dgettext('tuleap-project_ownership', 'Project ownership'),
            self::PANE_SHORTNAME,
            $callback
        );
    }
}
