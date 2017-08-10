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

namespace Tuleap\Dashboard;

use Countable;
use Tuleap\Event\Dispatchable;

class CollectionOfWidgetsThatNeedJavascriptDependencies implements Dispatchable, Countable
{
    const NAME = 'get_widgets_that_need_javascript_dependencies';

    /**
     * @var string[]
     */
    private $widgets = array();

    public function add($widget_name, array $javascript_files)
    {
        $this->widgets[$widget_name] = $javascript_files;
    }

    public function get($widget_name)
    {
        if (! isset($this->widgets[$widget_name])) {
            return array();
        }

        return $this->widgets[$widget_name];
    }

    public function count()
    {
        return count($this->widgets);
    }
}
