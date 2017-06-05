<?php
/**
 * Copyright (c) Enalean, 2017. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard;

use Codendi_HTMLPurifier;

abstract class DashboardPresenter
{
    public $name;
    public $id;

    public $is_active;
    public $delete_confirm;
    public $has_widget;
    public $widget_lines;

    public $url_add_widget;
    public $purified_too_many_columns_label;

    public function __construct(Dashboard $dashboard, $is_active, array $widget_lines)
    {
        $this->id           = $dashboard->getId();
        $this->name         = $dashboard->getName();
        $this->widget_lines = $widget_lines;
        $this->has_widget   = count($this->widget_lines) > 0;

        $this->is_active = $is_active;

        $this->delete_confirm = sprintf(
            _(
                'You are about to delete the dashboard "%s".
                This action is irreversible. Please confirm this deletion.'
            ),
            $this->name
        );

        $this->purified_too_many_columns_label = Codendi_HTMLPurifier::instance()->purify(
            _('Beyond 3 columns, each column<br>will have the same size.'),
            CODENDI_PURIFIER_LIGHT
        );
    }
}
