<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Layout\BreadCrumbDropdown;

class BreadCrumbPresenterBuilder
{
    /**
     *
     * @return BreadCrumbPresenter[]
     */
    public function build(BreadCrumbCollection $collection)
    {
        $presenters = [];
        foreach ($collection->getBreadcrumbs() as $breadcrumb) {
            $item_presenter = new BreadCrumbPresenter(
                new BreadCrumbLinkPresenter($breadcrumb->getLink()),
                $this->getSectionsPresenters($breadcrumb->getSubItems())
            );

            $presenters[] = $item_presenter;
        }

        return $presenters;
    }

    private function getSectionsPresenters(BreadCrumbSubItems $sub_items)
    {
        $presenters = [];
        foreach ($sub_items->getSections() as $section) {
            $presenters[] = new SubItemsSectionPresenter(
                $section->getLabel(),
                $this->getLinksPresenters($section)
            );
        }

        return $presenters;
    }

    private function getLinksPresenters(SubItemsSection $section)
    {
        $presenters = [];
        foreach ($section->getLinks() as $link) {
            $presenters[] = new BreadCrumbLinkPresenter($link);
        }

        return $presenters;
    }
}
