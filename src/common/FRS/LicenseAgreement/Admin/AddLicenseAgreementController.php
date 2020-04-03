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
use Tuleap\FRS\LicenseAgreement\NewLicenseAgreement;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ProjectRetriever;

class AddLicenseAgreementController implements DispatchableWithRequest
{
    /**
     * @var ProjectRetriever
     * @psalm-readonly
     */
    private $project_retriever;
    /**
     * @var \TemplateRenderer
     * @psalm-readonly
     */
    private $content_renderer;
    /**
     * @var \CSRFSynchronizerToken
     * @psalm-readonly
     */
    private $csrf_token;
    /**
     * @var IncludeAssets
     * @psalm-readonly
     */
    private $assets;
    /**
     * @var LicenseAgreementControllersHelper
     * @psalm-readonly
     */
    private $helper;

    public function __construct(
        ProjectRetriever $project_retriever,
        LicenseAgreementControllersHelper $helper,
        \TemplateRenderer $content_renderer,
        \CSRFSynchronizerToken $csrf_token,
        IncludeAssets $assets
    ) {
        $this->project_retriever = $project_retriever;
        $this->helper            = $helper;
        $this->content_renderer  = $content_renderer;
        $this->csrf_token        = $csrf_token;
        $this->assets            = $assets;
    }

    public static function buildSelf(): self
    {
        return new self(
            ProjectRetriever::buildSelf(),
            new LicenseAgreementControllersHelper(
                FRSPermissionManager::build(),
                \TemplateRendererFactory::build(),
            ),
            \TemplateRendererFactory::build()->getRenderer(__DIR__ . '/templates'),
            SaveLicenseAgreementController::getCSRFTokenSynchronizer(),
            new IncludeAssets(__DIR__ . '/../../../../www/assets/core', '/assets/core'),
        );
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->project_retriever->getProjectFromId($variables['project_id']);
        $this->helper->assertCanAccess($project, $request->getCurrentUser());
        $layout->includeFooterJavascriptFile($this->assets->getFileURL('frs-admin-license-agreement.js'));

        $this->helper->renderHeader($project);
        $this->content_renderer->renderToPage(
            'edit-license-agreement',
            new EditLicenseAgreementPresenter(
                $project,
                new NewLicenseAgreement(
                    '',
                    ''
                ),
                $this->csrf_token,
                false
            )
        );
        $layout->footer([]);
    }

    public static function getUrl(Project $project): string
    {
        return sprintf('/file/%d/admin/license-agreements/add', $project->getID());
    }
}
