<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST\v1;

use Tuleap\Tracker\Milestone\PaneInfo;

class PaneInfoRepresentation
{
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $icon_name;
    /**
     * @var string
     */
    public $uri;
    /**
     * @var string
     */
    public $identifier;

    public function build(PaneInfo $pane_info)
    {
        $this->title      = $pane_info->getTitle();
        $this->icon_name  = $pane_info->getIconName();
        $this->uri        = $pane_info->getUri();
        $this->identifier = $pane_info->getIdentifier();
    }
}
