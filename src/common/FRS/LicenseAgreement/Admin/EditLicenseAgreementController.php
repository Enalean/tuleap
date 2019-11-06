<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\LicenseAgreement\Admin;

use HTTPRequest;
use Project;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\GetProjectTrait;
use Tuleap\Request\NotFoundException;

class EditLicenseAgreementController implements DispatchableWithRequest, DispatchableWithProject
{
    use GetProjectTrait;

    /**
     * @var FRSPermissionManager
     */
    private $permission_manager;
    /**
     * @var LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var \TemplateRendererFactory
     */
    private $renderer_factory;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var IncludeAssets
     */
    private $assets;

    public function __construct(
        \ProjectManager $project_manager,
        \TemplateRendererFactory $renderer_factory,
        FRSPermissionManager $permission_manager,
        LicenseAgreementFactory $factory,
        \CSRFSynchronizerToken $csrf_token,
        IncludeAssets $assets
    ) {
        $this->project_manager    = $project_manager;
        $this->permission_manager = $permission_manager;
        $this->renderer_factory   = $renderer_factory;
        $this->factory            = $factory;
        $this->csrf_token         = $csrf_token;
        $this->assets             = $assets;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout  $layout
     * @param array       $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        $helper = new LicenseAgreementControllersHelper($this->permission_manager, $this->renderer_factory);
        $helper->assertCanAccess($project, $request->getCurrentUser());

        $license = $this->factory->getLicenseAgreementById($project, (int) $variables['id']);
        if (! $license) {
            throw new NotFoundException('Invalid license id');
        }

        $content_renderer = $this->renderer_factory->getRenderer(__DIR__ . '/templates');

        $layout->includeFooterJavascriptFile($this->assets->getFileURL('frs-admin-license-agreement.js'));

        $helper->renderHeader($project);
        if ($license instanceof DefaultLicenseAgreement) {
            $content_renderer->renderToPage('view-default-license-agreement', new ViewDefaultLicensePresenter($project));
        } else {
            $content_renderer->renderToPage('edit-license-agreement', new EditLicenseAgreementPresenter($project, $license, $this->csrf_token));
        }
        $layout->footer([]);
    }

    public static function getUrl(Project $project, LicenseAgreementInterface $agreement): string
    {
        return sprintf('/file/%d/admin/license-agreements/%d', $project->getID(), $agreement->getId());
    }
}
