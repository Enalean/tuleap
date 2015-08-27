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

class Planning_Presenter_EmptyHomePresenter {

    /** @var int */
    public $group_id;

    /** @var bool */
    public $is_user_admin;

    public function __construct(
        $group_id,
        $is_user_admin
    ) {
        $this->group_id      = $group_id;
        $this->is_user_admin = $is_user_admin;
    }

    public function nothing_set_up() {
        if (! $this->is_user_admin) {
            return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_generic');
        }

        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_admin', array('/plugins/agiledashboard/?group_id='.$this->group_id.'&action=admin'));
    }

    public function come_back_later() {
        return $GLOBALS['Language']->getText('plugin_agiledashboard', 'nothing_set_up_come_back');
    }
}