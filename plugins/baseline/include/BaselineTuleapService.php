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

namespace Tuleap\Baseline;

use Override;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class BaselineTuleapService extends \Service
{
    #[Override]
    public function getIconName(): string
    {
        return 'fas fa-tlp-baseline';
    }

    #[Override]
    public function getUrl(?string $url = null): string
    {
        return '/plugins/baseline/' . urlencode($this->project->getUnixNameLowerCase());
    }

    #[Override]
    public function urlCanChange(): bool
    {
        return false;
    }

    public function displayAdministrationHeader(): void
    {
        $crumb = new BreadCrumb(
            new BreadCrumbLink(
                dgettext('tuleap-baseline', 'Baselines'),
                $this->getUrl()
            )
        );

        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection(
                    [
                        new BreadCrumbLink(
                            dgettext('tuleap-baseline', 'Administration'),
                            ServiceAdministrationController::getAdminUrl($this->project)
                        ),
                    ]
                )
            )
        );
        $crumb->setSubItems($sub_items);

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($crumb);

        $this->displayHeader(
            dgettext('tuleap-baseline', 'Baselines administration'),
            $breadcrumbs,
            []
        );
    }
}
