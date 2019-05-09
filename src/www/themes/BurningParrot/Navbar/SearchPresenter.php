<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use PFUser;

class SearchPresenter
{
    /** @var string */
    public $placeholder;
    public $search_label;

    /** @var bool */
    public $is_user_allowed_to_search;

    public function __construct(PFUser $current_user)
    {
        $this->is_user_allowed_to_search = $current_user->isActive();

        $this->placeholder  = $GLOBALS['Language']->getText('include_menu', 'search');
        $this->search_label = $GLOBALS['Language']->getText('include_menu', 'search');
    }
}
