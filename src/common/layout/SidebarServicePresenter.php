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

namespace Tuleap\Layout;

class SidebarServicePresenter
{
    public $link;
    public $icon;
    public $name;
    public $label;
    public $enabled;
    public $description;
    public $id;
    public $is_project_defined = false;

    public function __construct(string $id, string $name, string $link, string $icon, string $label, string $description, bool $enabled)
    {
        $this->id = $id;
        $this->name = $name;
        $this->link = $link;
        $this->label = $label;
        $this->description = $description;
        $this->enabled = $enabled;
        $this->icon = $icon;
    }
}
