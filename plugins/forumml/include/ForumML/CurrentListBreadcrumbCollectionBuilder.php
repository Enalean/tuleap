<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ForumML;

use HTTPRequest;
use Project;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\MailingList\MailingListPresenterBuilder;

class CurrentListBreadcrumbCollectionBuilder
{
    /**
     * @var MailingListPresenterBuilder
     */
    private $list_presenter_builder;

    public function __construct(MailingListPresenterBuilder $list_presenter_builder)
    {
        $this->list_presenter_builder = $list_presenter_builder;
    }

    /**
     * @psalm-param array{group_id: int, list_name: string, is_public: int, description: string, group_list_id: int} $row
     */
    public function getCurrentListBreadcrumbCollectionFromRow(
        array $row,
        Project $project,
        HTTPRequest $request,
        string $list_name,
    ): BreadCrumbCollection {
        $list_presenter  = $this->list_presenter_builder->buildFromRow($row, $project, $request);
        $list_breadcrumb = new BreadCrumb(
            new BreadCrumbLink(
                $list_name,
                $list_presenter->public_url,
            )
        );
        $sub_items       = new BreadCrumbSubItems();
        $sub_items->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection(
                    [
                        new BreadCrumbLink(
                            _('(Un)Subscribe/Preferences'),
                            $list_presenter->subscribe_url,
                        ),
                        new BreadCrumbLink(
                            _('Administration'),
                            $list_presenter->admin_url,
                        ),
                    ]
                )
            )
        );
        $list_breadcrumb->setSubItems($sub_items);

        $breadcrumbs = new BreadCrumbCollection();
        $breadcrumbs->addBreadCrumb($list_breadcrumb);

        return $breadcrumbs;
    }
}
