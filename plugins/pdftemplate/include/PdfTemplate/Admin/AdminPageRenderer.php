<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\PdfTemplate\Admin;

use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\Admin\AdminSidebarPresenterBuilder;
use Tuleap\BuildVersion\FlavorFinderFromLicense;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\SidebarPresenter;
use Tuleap\SeatManagement\CachedLicenseBuilder;

final class AdminPageRenderer implements RenderAPresenter
{
    #[\Override]
    public function renderAPresenter(
        BaseLayout $layout,
        \PFUser $user,
        string $title,
        string $template_path,
        string $template_name,
        mixed $presenter,
    ): void {
        $this->header($layout, $user, $title);
        $this->renderToPage($template_path, $template_name, $presenter);
        $this->footer($layout);
    }

    public function header(
        BaseLayout $layout,
        \PFUser $user,
        string $title,
    ): void {
        $configuration = HeaderConfigurationBuilder::get($title);

        if ($user->isSuperUser()) {
            $configuration = $configuration->inSiteAdministration(
                new SidebarPresenter(
                    'siteadmin-sidebar',
                    $this->renderSideBar(),
                    VersionPresenter::fromFlavorFinder(new FlavorFinderFromLicense(CachedLicenseBuilder::instance()))
                )
            );
        } else {
            $configuration = $configuration->inSiteAdministrationWithoutSidebar();
        }

        $layout->header($configuration->build());
    }

    public function renderToPage(string $template_path, string $template_name, mixed $presenter): void
    {
        $this->getRenderer($template_path)->renderToPage($template_name, $presenter);
    }

    private function getRenderer(string $template_path): TemplateRenderer
    {
        return TemplateRendererFactory::build()->getRenderer($template_path);
    }

    public function footer(BaseLayout $layout): void
    {
        $layout->footer(FooterConfiguration::withoutContent());
    }

    private function renderSideBar(): string
    {
        $presenter = (new AdminSidebarPresenterBuilder())->build();

        return $this
            ->getRenderer(__DIR__ . '/../../../../../src/templates/admin/')
            ->renderToString('sidebar', $presenter);
    }
}
