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
use Tuleap\FRS\LicenseAgreement\InvalidLicenseAgreementException;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;

class SetDefaultLicenseAgreementController implements DispatchableWithRequest
{
    private const CSRF_TOKEN = 'frs_set_default_license_agreement';

    /**
     * @var ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var LicenseAgreementControllersHelper
     */
    private $helper;

    public function __construct(
        ProjectRetriever $project_retriever,
        LicenseAgreementControllersHelper $helper,
        LicenseAgreementFactory $factory,
        \CSRFSynchronizerToken $csrf_token
    ) {
        $this->project_retriever = $project_retriever;
        $this->helper            = $helper;
        $this->factory           = $factory;
        $this->csrf_token        = $csrf_token;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new LicenseAgreementControllersHelper(
                FRSPermissionManager::build(),
                \TemplateRendererFactory::build(),
            ),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
            SetDefaultLicenseAgreementController::getCSRFTokenSynchronizer(),
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);

        $this->csrf_token->check(ListLicenseAgreementsController::getUrl($project));
        $this->helper->assertCanAccess($project, $request->getCurrentUser());

        if (! $request->existAndNonEmpty('default_agreement')) {
            throw new InvalidLicenseAgreementException('Agreement id is missing');
        }
        $agreement_id = (int) $request->getValidated('default_agreement', 'int');
        $license = $this->factory->getLicenseAgreementById($project, $agreement_id);
        if (! $license) {
            throw new InvalidLicenseAgreementException('Invalid license agreement for project');
        }

        $this->factory->setProjectDefault($project, $license);

        $layout->redirect(ListLicenseAgreementsController::getUrl($project));
    }

    public static function getUrl(Project $project): string
    {
        return sprintf('/file/%d/admin/license-agreements/set-default', $project->getID());
    }

    public static function getCSRFTokenSynchronizer()
    {
        return new \CSRFSynchronizerToken(self::CSRF_TOKEN);
    }
}
