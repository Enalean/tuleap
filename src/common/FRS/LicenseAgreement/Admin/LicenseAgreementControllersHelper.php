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

use PFUser;
use Project;
use TemplateRendererFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\ToolbarPresenter;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class LicenseAgreementControllersHelper
{
    /**
     * @var FRSPermissionManager
     */
    private $permission_manager;
    /**
     * @var TemplateRendererFactory
     */
    private $renderer_factory;

    public function __construct(FRSPermissionManager $permission_manager, TemplateRendererFactory $renderer_factory)
    {
        $this->permission_manager = $permission_manager;
        $this->renderer_factory   = $renderer_factory;
    }

    public function assertCanAccess(Project $project, PFUser $current_user): void
    {
        if (! $this->permission_manager->isAdmin($project, $current_user)) {
            throw new ForbiddenException('Only for files administrators');
        }

        $this->getFileService($project);
    }

    public function renderHeader(Project $project): void
    {
        $toolbar_presenter = new ToolbarPresenter($project);
        $toolbar_presenter->setLicenseAgreementIsActive();
        $toolbar_presenter->displaySectionNavigation();

        $this->getFileService($project)->displayFRSHeader($project, _('Files Administration'));
        $header_renderer = $this->renderer_factory->getRenderer(__DIR__ . '/../../../../templates/frs');
        $header_renderer->renderToPage('toolbar-presenter', $toolbar_presenter);
    }

    private function getFileService(Project $project): \ServiceFile
    {
        $file_service = $project->getService(\Service::FILE);
        if (! $file_service instanceof \ServiceFile) {
            throw new NotFoundException('Service is not active for this project');
        }
        return $file_service;
    }
}
