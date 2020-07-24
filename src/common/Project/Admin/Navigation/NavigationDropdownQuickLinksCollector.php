<?php
/**
 * Copyright Enalean (c) 2017-2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\Admin\Navigation;

use Project;
use Tuleap\Event\Dispatchable;

class NavigationDropdownQuickLinksCollector implements Dispatchable
{
    public const NAME = 'collect_project_admin_navigation_permission_dropdown_quick_links';

    private $quick_links_collection;
    private $project;

    public function __construct(Project $projet)
    {
        $this->project                = $projet;
        $this->quick_links_collection = [];
    }

    public function addQuickLink(NavigationDropdownItemPresenter $quick_link)
    {
        $this->quick_links_collection[] = $quick_link;
    }

    public function getQuickLinksList()
    {
        return $this->quick_links_collection;
    }

    public function getProject()
    {
        return $this->project;
    }
}
