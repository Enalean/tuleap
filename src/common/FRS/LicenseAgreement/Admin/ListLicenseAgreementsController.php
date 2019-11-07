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

use CSRFSynchronizerToken;
use HTTPRequest;
use Project;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\NoLicenseToApprove;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\GetProjectTrait;
use Tuleap\Request\NotFoundException;

class ListLicenseAgreementsController implements DispatchableWithRequest, DispatchableWithProject
{
    use GetProjectTrait;

    /**
     * @var \TemplateRendererFactory
     */
    private $renderer_factory;
    /**
     * @var FRSPermissionManager
     */
    private $permission_manager;
    /**
     * @var LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(\ProjectManager $project_manager, \TemplateRendererFactory $renderer_factory, FRSPermissionManager $permission_manager, LicenseAgreementFactory $factory)
    {
        $this->project_manager    = $project_manager;
        $this->renderer_factory   = $renderer_factory;
        $this->permission_manager = $permission_manager;
        $this->factory            = $factory;
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

        $license_agreement_default = $this->factory->getDefaultLicenseAgreementForProject($project);

        $license_agreements = [
            new LicenseAgreementPresenter($project, new NoLicenseToApprove(), $license_agreement_default),
            new LicenseAgreementPresenter($project, new DefaultLicenseAgreement(), $license_agreement_default),
        ];
        foreach ($this->factory->getProjectLicenseAgreements($project) as $license_agreement) {
            $license_agreements []= new LicenseAgreementPresenter($project, $license_agreement, $license_agreement_default);
        }

        $helper->renderHeader($project);
        $content_renderer = $this->renderer_factory->getRenderer(__DIR__ . '/templates');
        $content_renderer->renderToPage('license-agreements-list', new ListLicenseAgreementsPresenter($project, $license_agreements));
        $layout->footer([]);
    }

    public static function getUrl(Project $project): string
    {
        return sprintf('/file/%d/admin/license-agreements', $project->getID());
    }
}
