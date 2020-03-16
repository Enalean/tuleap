<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Tuleap_Tour
{

    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $storage = false;

    /**
     * @var bool
     */
    public $orphan = true;

    /**
     * @var array
     */
    public $steps;

    /**
     * @var string
     */
    public $template;

    public function __construct($name, array $steps)
    {
        $this->name  = $name;
        $this->steps = $steps;

        $this->initTemplate();
    }

    private function initTemplate()
    {
        $this->template = '<div class="popover tour">
        <div class="arrow"></div>
        <h3 class="popover-title"></h3>
        <div class="popover-content"></div>
        <div class="popover-navigation">
            <button class="btn btn-small" data-role="prev">
                ' . $GLOBALS['Language']->getText('tour', 'previous_button') . '
            </button>
            <button class="btn btn-small" data-role="next">
                ' . $GLOBALS['Language']->getText('tour', 'next_button') . '
            </button>
            <button class="btn btn-small" data-role="end">
                ' . $GLOBALS['Language']->getText('tour', 'end_button') . '
            </button>
            <div style="clear: both"></div>
        </div>
        </nav>
    </div>';
    }
}
