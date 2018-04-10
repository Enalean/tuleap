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

namespace Tuleap\layout\BreadCrumbDropdown;

use Codendi_HTMLPurifier;
use Tuleap\Sanitizer\URISanitizer;

class BreadCrumbPresenterBuilder
{
    /** @var URISanitizer */
    private $uri_sanitizer;

    public function __construct(URISanitizer $uri_sanitizer)
    {
        $this->uri_sanitizer = $uri_sanitizer;
    }

    /**
     * @param BreadCrumbCollection $collection
     * @return BreadCrumbPresenter[]
     */
    public function build(BreadCrumbCollection $collection)
    {
        $presenters = [];
        foreach ($collection->getBreadcrumbs() as $breadcrumb) {
            $item_presenter = new BreadCrumbPresenter($breadcrumb);
            $sub_items      = [];
            foreach ($breadcrumb->getSubItems()->getItems() as $sub_item) {
                $sub_items[] = new BreadCrumbPresenter($sub_item);
            }
            $item_presenter->setSubItems($sub_items);
            $presenters[] = $item_presenter;
        }

        return $presenters;
    }
}
