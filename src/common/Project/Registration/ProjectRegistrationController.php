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
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

final class ProjectRegistrationController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var ProjectRegistrationPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var TemplateRendererFactory
     */
    private $template_renderer_factory;
    /**
     * @var ProjectRegistrationUserPermissionChecker
     */
    private $permission_checker;

    public function __construct(
        TemplateRendererFactory $template_renderer_factory,
        private JavascriptAssetGeneric $assets,
        ProjectRegistrationUserPermissionChecker $permission_checker,
        ProjectRegistrationPresenterBuilder $presenter_builder,
    ) {
        $this->template_renderer_factory = $template_renderer_factory;
        $this->presenter_builder         = $presenter_builder;
        $this->permission_checker        = $permission_checker;
    }

    /**
     * @throws \Tuleap\Request\ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        try {
            $this->permission_checker->checkUserCreateAProject($request->getCurrentUser());
        } catch (RegistrationForbiddenException $exception) {
            throw new ForbiddenException();
        }

        $layout->addJavascriptAsset($this->assets);

        $layout->header(\Tuleap\Layout\HeaderConfiguration::fromTitle(_("Project Registration")));

        $this->template_renderer_factory
            ->getRenderer(__DIR__ . '/../../../templates/project/registration/')
            ->renderToPage('project-registration', $this->presenter_builder->buildPresenter());
        $layout->footer(FooterConfiguration::withoutContent());
    }
}
