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

namespace Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\History;

use PFUser;
use Tuleap\Theme\BurningParrot\Navbar\DropdownMenuItem\Content\Presenter;

class UserHistoryPresenter extends Presenter
{
    public $is_history = true;

    /** @var int */
    public $current_user_id;

    /** @var string */
    public $empty_history;


    public function __construct(
        $id,
        PFUser $current_user
    ) {
        parent::__construct($id);

        $this->current_user_id = $current_user->getId();
        $this->empty_history   = _('Your history is empty');
    }
}
