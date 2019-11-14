<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration;

use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

final class ProjectRegistrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;
    /**
     * @var IncludeAssets
     */
    private $registration_assets;

    public function __construct(
        TemplateRendererFactory $template_renderer_factory,
        IncludeAssets $registration_assets,
        ProjectRegistrationUserPermissionChecker $permission_checker
    ) {
        $this->template_renderer_factory = $template_renderer_factory;
        $this->permission_checker        = $permission_checker;
        $this->registration_assets       = $registration_assets;
    }

    /**
     * @throws \Tuleap\Request\ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $this->permission_checker->checkUserCreateAProject($request);

        $layout->includeFooterJavascriptFile($this->registration_assets->getFileURL('project-registration.js'));
        $layout->addCssAsset(
            new CssAsset(
                new IncludeAssets(
                    __DIR__ . '/../../../../src/www/assets/project-registration/themes',
                    '/assets/project-registration/themes'
                ),
                'tlp'
            )
        );
        $layout->header(["title" => _("Project Registration")]);

        $this->template_renderer_factory
            ->getRenderer(__DIR__ . '/../../../templates/project/registration/')
            ->renderToPage('project-registration', []);
        $layout->footer(["without_content" => true]);
    }
}
