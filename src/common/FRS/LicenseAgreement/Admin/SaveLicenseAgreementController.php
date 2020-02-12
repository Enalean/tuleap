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
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\FRS\LicenseAgreement\NewLicenseAgreement;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

class SaveLicenseAgreementController implements DispatchableWithRequest
{
    private const CSRF_TOKEN = 'frs_edit_license_agreement';

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
    /**
     * @var ProjectRetriever
     */
    private $project_retriever;

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
            SaveLicenseAgreementController::getCSRFTokenSynchronizer(),
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);

        $this->csrf_token->check(ListLicenseAgreementsController::getUrl($project));

        $this->helper->assertCanAccess($project, $request->getCurrentUser());

        if ($request->exist('delete')) {
            if (! $request->existAndNonEmpty('id')) {
                throw new NotFoundException('Invalid license id');
            }

            $license = $this->getLicenseFromRequest($request, $project);
            $this->factory->delete($project, $license);
            $layout->addFeedback(\Feedback::INFO, sprintf(_('License "%s" was successfully deleted'), $license->getTitle()));
        }

        if ($request->exist('save')) {
            if ($request->existAndNonEmpty('id')) {
                $license = $this->getLicenseFromRequest($request, $project);

                $new_license = new LicenseAgreement(
                    $license->getId(),
                    (string) $request->getValidated('title', 'string', ''),
                    (string) $request->getValidated('content', 'text', ''),
                );
            } else {
                $new_license = new NewLicenseAgreement(
                    (string) $request->getValidated('title', 'string', ''),
                    (string) $request->getValidated('content', 'text', ''),
                );
            }

            $this->factory->save($project, $new_license);
        }

        $layout->redirect(ListLicenseAgreementsController::getUrl($project));
    }

    public static function getUrl(Project $project): string
    {
        return sprintf('/file/%d/admin/license-agreements/save', $project->getID());
    }

    public static function getCSRFTokenSynchronizer()
    {
        return new \CSRFSynchronizerToken(self::CSRF_TOKEN);
    }

    private function getLicenseFromRequest(HTTPRequest $request, Project $project): LicenseAgreementInterface
    {
        $license = $this->factory->getLicenseAgreementById($project, (int) $request->get('id'));
        if (! $license) {
            throw new NotFoundException('Invalid license id');
        }
        if (! $license->isModifiable()) {
            throw new ForbiddenException('You cannot modify this license');
        }
        return $license;
    }
}
