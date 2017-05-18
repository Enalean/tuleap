<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Dashboard\Widget;

use Codendi_Request;
use Widget;

class WidgetCreator
{
    /**
     * @var DashboardWidgetDao
     */
    private $dao;

    public function __construct(DashboardWidgetDao $dao)
    {
        $this->dao = $dao;
    }

    public function create($owner_id, $owner_type, $dashboard_id, Widget $widget, Codendi_Request $request)
    {
        $content_id = 0;
        if (! $widget->isUnique()) {
            $content_id = $widget->create($request);
        }

        $this->dao->create($owner_id, $owner_type, $dashboard_id, $widget->getId(), $content_id);
    }
}
