<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\Theme\BurningParrot\Navbar;

class Presenter
{
    /** @var GlobalNavPresenter */
    public $global_nav_presenter;

    /** @var SearchPresenter */
    public $search_presenter;

    /** @var UserNavPresenter */
    public $user_nav_presenter;

    /** @var JoinCommunityPresenter */
    public $community_presenter;

    public $homepage_label;

    public function __construct(
        GlobalNavPresenter $global_nav_presenter,
        SearchPresenter $search_presenter,
        UserNavPresenter $user_nav_presenter,
        JoinCommunityPresenter $community_presenter
    ) {
        $this->global_nav_presenter = $global_nav_presenter;
        $this->search_presenter     = $search_presenter;
        $this->user_nav_presenter   = $user_nav_presenter;
        $this->community_presenter  = $community_presenter;
        $this->homepage_label       = _('Homepage');
    }
}
