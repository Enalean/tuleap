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
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\GetProjectTrait;
use Tuleap\Request\NotFoundException;

class SaveLicenseAgreementController implements DispatchableWithRequest, DispatchableWithProject
{
    use GetProjectTrait;

    private const CSRF_TOKEN = 'frs_edit_license_agreement';

    /**
     * @var FRSPermissionManager
     */
    private $permission_manager;
    /**
     * @var LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var \CSRFSynchronizerToken
     */
    private $csrf_token;

    public function __construct(\ProjectManager $project_manager, FRSPermissionManager $permission_manager, LicenseAgreementFactory $factory, \CSRFSynchronizerToken $csrf_token)
    {
        $this->project_manager    = $project_manager;
        $this->permission_manager = $permission_manager;
        $this->factory            = $factory;
        $this->csrf_token         = $csrf_token;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);

        $this->csrf_token->check(ListLicenseAgreementsController::getUrl($project));

        if (! $this->permission_manager->isAdmin($project, $request->getCurrentUser())) {
            throw new ForbiddenException('Only for files administrators');
        }

        $file_service = $project->getFileService();
        if (! $file_service) {
            throw new NotFoundException('Service is not active for this project');
        }

        $license = $this->factory->getLicenseAgreementById($project, (int) $variables['id']);
        if (! $license) {
            throw new NotFoundException('Invalid license id');
        }
        if (! $license instanceof LicenseAgreement) {
            throw new ForbiddenException('You cannot modify this license');
        }

        $new_license = new LicenseAgreement(
            $license->getId(),
            (string) $request->getValidated('title', 'string', ''),
            (string) $request->getValidated('content', 'text', ''),
        );

        $this->factory->save($new_license);

        $layout->redirect(ListLicenseAgreementsController::getUrl($project));
    }

    public static function getUrl(Project $project, LicenseAgreementInterface $agreement): string
    {
        return sprintf('/file/%d/admin/license-agreements/%d', $project->getID(), $agreement->getId());
    }

    public static function getCSRFTokenSynchronizer()
    {
        return new \CSRFSynchronizerToken(self::CSRF_TOKEN);
    }
}
