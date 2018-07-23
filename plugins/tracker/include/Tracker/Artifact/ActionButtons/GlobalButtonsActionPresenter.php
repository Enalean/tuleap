<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact\ActionButtons;

class GlobalButtonsActionPresenter
{
    /**
     * @var array
     */
    public $action_buttons;
    /**
     * @var bool
     */
    public $has_at_least_one_action;

    public function __construct(array $action_buttons)
    {
        $this->action_buttons          = $action_buttons;
        $this->has_at_least_one_action = count($action_buttons) > 0;
    }
}
