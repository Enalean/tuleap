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

namespace Tuleap\Dashboard\User;

use CSRFSynchronizerToken;

class MyPresenter
{
    /**
     * @var UserPresenter
     */
    public $user_presenter;
    /**
     * @var DashboardPresenter[]
     */
    public $user_dashboards;

    public $add_dashboard_label;
    public $dashboard_name_label;

    public $cancel;
    public $close;
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;
    public $delete_dashboard_label;
    public $edit_dashboard_label;
    public $delete_dashboard_title;
    public $edit_dashboard_title;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        UserPresenter $user_presenter,
        array $user_dashboards
    ) {
        $this->csrf_token       = $csrf;
        $this->user_presenter   = $user_presenter;
        $this->user_dashboards  = $user_dashboards;

        $this->add_dashboard_label  = dgettext(
            'tuleap-core',
            'Add dashboard'
        );
        $this->delete_dashboard_title  = dgettext(
            'tuleap-core',
            'Delete dashboard'
        );
        $this->delete_dashboard_label  = dgettext(
            'tuleap-core',
            'Delete dashboard'
        );
        $this->edit_dashboard_title  = dgettext(
            'tuleap-core',
            'Edit dashboard'
        );
        $this->edit_dashboard_label = dgettext(
            'tuleap-core',
            'Edit dashboard'
        );
        $this->dashboard_name_label = dgettext(
            'tuleap-core',
            'Dashboard name'
        );

        $this->cancel = dgettext(
            'tuleap-core',
            'Cancel'
        );
        $this->close = dgettext(
            'tuleap-core',
            'Close'
        );
    }
}
