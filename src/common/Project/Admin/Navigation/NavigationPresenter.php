<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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

class NavigationPresenter implements Dispatchable
{
    public const NAME = 'collect_project_admin_navigation_items';

    public $admin_section_title;

    /**
     * @var NavigationItem[]
     */
    private $entries;

    /** @var Project */
    private $project;

    public function __construct(array $entries, Project $project)
    {
        $this->admin_section_title    = _("Project administration");
        $this->entries                = $entries;
        $this->project                = $project;
    }

    public function addDropdownItem($entry_shortname, NavigationDropdownItemPresenter $dropdown_item)
    {
        $dropdown_presenter = $this->entries[$entry_shortname];

        $dropdown_presenter->menu_items[] = $dropdown_item;
    }

    public function addItem(NavigationItemPresenter $item)
    {
        $this->entries[$item->shortname] = $item;
    }

    /**
     * @return NavigationItem[]
     */
    public function getEntries()
    {
        return array_values($this->entries);
    }

    public function hasEntries(): bool
    {
        return ! empty($this->entries);
    }

    public function getProjectId()
    {
        return $this->project->getID();
    }
}
