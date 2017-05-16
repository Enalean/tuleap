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
use Tuleap\Dashboard\PagePresenter;

class MyPresenter extends PagePresenter
{
    /**
     * @var UserPresenter
     */
    public $user_presenter;
    /**
     * @var UserDashboardPresenter[]
     */
    public $dashboards;
    public $has_dashboard;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $url,
        UserPresenter $user_presenter,
        array $dashboards
    ) {
        parent::__construct($csrf, $url);

        $this->user_presenter = $user_presenter;
        $this->dashboards     = $dashboards;
        $this->has_dashboard  = count($dashboards) > 0;
    }
}
