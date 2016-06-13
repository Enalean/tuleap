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

class PresenterBuilder
{
    /** @var PFUser */
    private $current_user;

    public function build(PFUser $current_user)
    {
        $this->current_user = $current_user;

        return new Presenter(
            new GlobalNavPresenter(
                $this->getGlobalMenuItems()
            ),
            new SearchPresenter(),
            new UserNavPresenter(
                $this->current_user
            )
        );
    }

    private function getGlobalMenuItems()
    {
        return array(
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'projects'),
                'icon-archive',
                ''
            ),
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'extras'),
                'icon-ellipsis-horizontal',
                ''
            ),
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'help'),
                'icon-question-sign',
                ''
            ),
            new GlobalMenuItemPresenter(
                $GLOBALS['Language']->getText('include_menu', 'site_admin'),
                'icon-cog',
                'go-to-admin'
            )
        );
    }
}
