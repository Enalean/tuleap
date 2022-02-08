<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Widget\Event;

use Tuleap\Event\Dispatchable;

class GetWidget implements Dispatchable
{
    public const NAME = 'widgetInstance';

    private $widget_name;
    private $widget;

    public function __construct($widget_name)
    {
        $this->widget_name = $widget_name;
    }

    public function getName()
    {
        return $this->widget_name;
    }

    public function setWidget(\Widget $widget)
    {
        $this->widget = $widget;
    }

    public function getWidget()
    {
        return $this->widget;
    }
}
