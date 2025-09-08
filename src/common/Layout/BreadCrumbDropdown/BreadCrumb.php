<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

class BreadCrumb implements PresentableBreadCrumb
{
    private BreadCrumbSubItems $sub_items;
    private string $classname;

    public function __construct(private readonly BreadCrumbLink $link)
    {
        $this->sub_items = new BreadCrumbSubItems();
        $this->classname = '';
    }

    public function getLink(): BreadCrumbLink
    {
        return $this->link;
    }

    public function getSubItems(): BreadCrumbSubItems
    {
        return $this->sub_items;
    }

    public function setSubItems(BreadCrumbSubItems $sub_items): void
    {
        $this->sub_items = $sub_items;
    }

    public function setAdditionalClassname(string $classname): void
    {
        $this->classname = $classname;
    }

    public function getAdditionalClassname(): string
    {
        return $this->classname;
    }

    #[\Override]
    public function getBreadCrumbPresenter(): BreadCrumbPresenter
    {
        return new BreadCrumbPresenter(
            $this->getAdditionalClassname(),
            new BreadCrumbLinkPresenter($this->getLink()),
            $this->getSectionsPresenters($this->getSubItems())
        );
    }

    private function getSectionsPresenters(BreadCrumbSubItems $sub_items): array
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

    private function getLinksPresenters(SubItemsSection $section): array
    {
        $presenters = [];
        foreach ($section->getLinks() as $link) {
            $presenters[] = new BreadCrumbLinkPresenter($link);
        }

        return $presenters;
    }
}
