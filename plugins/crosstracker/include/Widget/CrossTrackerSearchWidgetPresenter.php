<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use PFUser;

final readonly class CrossTrackerSearchWidgetPresenter
{
    public int $widget_id;
    public bool $is_widget_admin;
    public string $documentation_base_url;
    public string $dashboard_type;
    public string $title_attribute;
    public string $default_title;
    public int $dashboard_id;

    public function __construct(
        int $widget_id,
        bool $is_admin,
        PFUser $current_user,
        string $dashboard_type,
        string $title_attribute,
        string $default_title,
        int $dashboard_id,
    ) {
        $this->widget_id = $widget_id;

        $this->is_widget_admin = $is_admin;

        $this->documentation_base_url = '/doc/' . urlencode(
            $current_user->getShortLocale()
        );
        $this->dashboard_type         = $dashboard_type;
        $this->title_attribute        = $title_attribute;
        $this->default_title          = $default_title;
        $this->dashboard_id           = $dashboard_id;
    }
}
