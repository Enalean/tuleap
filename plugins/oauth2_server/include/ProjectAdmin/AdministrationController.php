<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\ProjectAdmin;

use HTTPRequest;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\Routing\AdministrationLayoutHelper;
use Tuleap\Project\Admin\Routing\LayoutHelper;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

class AdministrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public const PANE_SHORTNAME = 'oauth2_clients';
    /** @var LayoutHelper */
    private $layout_helper;
    /** @var TemplateRenderer */
    private $renderer;
    /** @var ProjectAdminPresenterBuilder */
    private $presenter_builder;

    public function __construct(
        LayoutHelper $layout_helper,
        TemplateRenderer $renderer,
        ProjectAdminPresenterBuilder $presenter_builder
    ) {
        $this->layout_helper     = $layout_helper;
        $this->renderer          = $renderer;
        $this->presenter_builder = $presenter_builder;
    }

    public static function buildSelf(): self
    {
        return new self(
            AdministrationLayoutHelper::buildSelf(),
            \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../templates'),
            ProjectAdminPresenterBuilder::buildSelf()
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $callback = function (\Project $project, \PFUser $user) {
            $this->renderer->renderToPage('project-admin', $this->presenter_builder->build($project));
        };
        $this->layout_helper->renderInProjectAdministrationLayout(
            $request,
            $variables['project_id'],
            dgettext('tuleap-oauth2_server', 'OAuth2 Apps'),
            self::PANE_SHORTNAME,
            $callback
        );
    }
}
