<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\MailingList;

use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;

class ServiceMailingList extends \Service
{
    public function displayMailingListHeader(\PFUser $user, string $title): void
    {
        $this->displayMailingListHeaderWithAdditionalBreadcrumbs(
            $user,
            $title,
            new BreadCrumbCollection(),
        );
    }

    public function displayMailingListHeaderWithAdditionalBreadcrumbs(
        \PFUser $user,
        string $title,
        BreadCrumbCollection $additional_breadcrumbs,
    ): void {
        \Tuleap\Project\ServiceInstrumentation::increment('mailinglists');

        $breadcrumbs = $this->getBreadcrumbs($user);
        foreach ($additional_breadcrumbs->getBreadcrumbs() as $breadcrumb) {
            $breadcrumbs->addBreadCrumb($breadcrumb);
        }

        $this->displayHeader($title, $breadcrumbs, [], []);
    }

    private function getBreadcrumbs(\PFUser $user): BreadCrumbCollection
    {
        $lists_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                _('Lists'),
                '/mail/?' . http_build_query(
                    [
                        'group_id' => $this->project->getID(),
                    ]
                ),
            )
        );

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($lists_breadcrumb);

        if ($user->isAdmin((int) $this->project->getID())) {
            $sub_items = new BreadCrumbSubItems();
            $sub_items->addSection(
                new SubItemsUnlabelledSection(
                    new BreadCrumbLinkCollection(
                        [
                            new BreadCrumbLink(
                                _('Administration'),
                                MailingListAdministrationController::getUrl($this->project),
                            ),
                        ]
                    )
                )
            );
            $lists_breadcrumb->setSubItems($sub_items);
        }

        return $breadcrumbs;
    }
}
