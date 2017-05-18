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

namespace Tuleap\Dashboard\Widget;

use Codendi_HTMLPurifier;

class DashboardWidgetLinePresenter
{
    public $line_id;
    public $layout;
    /**
     * @var DashboardWidgetColumnPresenter[]
     */
    public $widget_columns;
    public $id;
    public $purified_too_many_columns_label;

    public function __construct(
        $line_id,
        $layout,
        array $widget_columns
    ) {
        $this->line_id        = $line_id;
        $this->layout         = $layout;
        $this->widget_columns = $widget_columns;

        $this->purified_too_many_columns_label = Codendi_HTMLPurifier::instance()->purify(
            _('Beyond 3 columns, each column<br>will have the same size.'),
            CODENDI_PURIFIER_LIGHT
        );
    }
}
