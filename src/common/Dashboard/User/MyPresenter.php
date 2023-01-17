<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All rights reserved
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

final class MyPresenter extends PagePresenter
{
    public bool $has_dashboard;

    /**
     * @param UserDashboardPresenter[] $dashboards
     */
    public function __construct(
        CSRFSynchronizerToken $csrf,
        $url,
        public UserPresenter $user_presenter,
        public array $dashboards,
        public ?FirstTimerPresenter $first_timer,
    ) {
        parent::__construct($csrf, $url);

        $this->has_dashboard = count($this->dashboards) > 0;
    }
}
