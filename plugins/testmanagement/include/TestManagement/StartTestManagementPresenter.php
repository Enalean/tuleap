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

namespace Tuleap\TestManagement;

use CSRFSynchronizerToken;

class StartTestManagementPresenter
{
    /**
     * @var string
     */
    public $config_is_not_fully_set_up;

    /**
     * @var string
     */
    public $start_testmanagement;

    /**
     * @var string
     */
    public $help_message_config_not_fully_set_up;

    /**
     * @var string
     */
    public $come_back_later;

    /**
     * @var bool
     */
    public $is_user_admin;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    public function __construct(bool $is_user_admin, CSRFSynchronizerToken $csrf_token)
    {
        $this->is_user_admin = $is_user_admin;
        $this->csrf_token    = $csrf_token;

        $this->config_is_not_fully_set_up = dgettext(
            'tuleap-testmanagement',
            'TestManagement config is not fully set up,'
        );

        $this->start_testmanagement = dgettext(
            'tuleap-testmanagement',
            'Start TestManagement'
        );

        $this->help_message_config_not_fully_set_up = dgettext(
            'tuleap-testmanagement',
            'go in the admin section to set manually the trackers or let us do it for you.'
        );

        $this->come_back_later = dgettext(
            'tuleap-testmanagement',
            'come back later.'
        );
    }
}
