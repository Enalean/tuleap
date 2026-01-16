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

use Project;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementDao;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeAssetsGeneric;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Request\CSRFSynchronizerTokenInterface;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;

readonly class EditLicenseAgreementController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    public function __construct(
        private ProjectRetriever $project_retriever,
        private LicenseAgreementControllersHelper $helper,
        private \TemplateRendererFactory $renderer_factory,
        private LicenseAgreementFactory $factory,
        private CSRFSynchronizerTokenInterface $csrf_token,
        private IncludeAssets $assets,
        private IncludeAssetsGeneric $ckeditor_assets,
    ) {
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new LicenseAgreementControllersHelper(
                FRSPermissionManager::build(),
                \TemplateRendererFactory::build(),
            ),
            \TemplateRendererFactory::build(),
            new LicenseAgreementFactory(
                new LicenseAgreementDao()
            ),
            SaveLicenseAgreementController::getCSRFTokenSynchronizer(),
            new \Tuleap\Layout\IncludeCoreAssets(),
            new IncludeViteAssets(__DIR__ . '/../../../../scripts/ckeditor4/frontend-assets/', '/assets/core/ckeditor4/')
        );
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    #[\Override]
    public function process(\Tuleap\HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);
        $this->helper->assertCanAccess($project, $request->getCurrentUser());

        $license = $this->factory->getLicenseAgreementById($project, (int) $variables['id']);
        if (! $license || ! $license->isViewable()) {
            throw new NotFoundException('Invalid license id');
        }
        $can_be_deleted = false;
        $used_by        = [];
        if ($license->isModifiable()) {
            $can_be_deleted = $this->factory->canBeDeleted($project, $license);
            if (! $can_be_deleted) {
                foreach ($this->factory->getListOfPackagesForLicenseAgreement($license) as $package) {
                    $used_by[] = new UsedByPresenter($project, $package);
                }
            }
        }

        $content_renderer = $this->renderer_factory->getRenderer(__DIR__ . '/templates');

        $layout->includeFooterJavascriptFile($this->ckeditor_assets->getFileURL('ckeditor.js'));
        $layout->includeFooterJavascriptFile($this->assets->getFileURL('frs-admin-license-agreement.js'));

        $this->helper->renderHeader($project);
        if ($license->isModifiable()) {
            $content_renderer->renderToPage('edit-license-agreement', new EditLicenseAgreementPresenter($project, $license, $this->csrf_token, $can_be_deleted, ...$used_by));
        } else {
            $content_renderer->renderToPage('view-default-license-agreement', new ViewDefaultLicensePresenter($project));
        }
        $layout->footer([]);
    }

    public static function getUrl(Project $project, LicenseAgreementInterface $agreement): string
    {
        return sprintf('/file/%d/admin/license-agreements/%d', $project->getID(), $agreement->getId());
    }
}
