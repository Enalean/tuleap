<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\Layout\HeaderConfiguration\WithoutSidebar;
use Tuleap\Layout\NewDropdown\NewDropdownPresenter;

class Presenter
{
    /** @var UserNavPresenter */
    public $user_nav_presenter;
    /**
     * @var NewDropdownPresenter
     */
    public $new_dropdown;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_super_user;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $should_logo_be_displayed;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_legacy_logo_customized;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $is_svg_logo_customized;
    /**
     * @var bool
     * @psalm-readonly
     */
    public $can_buddies_be_invited;

    public function __construct(
        UserNavPresenter $user_nav_presenter,
        NewDropdownPresenter $new_dropdown,
        bool $is_super_user,
        bool $should_logo_be_displayed,
        bool $is_legacy_logo_customized,
        bool $is_svg_logo_customized,
        bool $can_buddies_be_invited,
        public ?WithoutSidebar $in_project_without_sidebar,
    ) {
        $this->user_nav_presenter        = $user_nav_presenter;
        $this->new_dropdown              = $new_dropdown;
        $this->is_super_user             = $is_super_user;
        $this->should_logo_be_displayed  = $should_logo_be_displayed;
        $this->is_legacy_logo_customized = $is_legacy_logo_customized;
        $this->is_svg_logo_customized    = $is_svg_logo_customized;
        $this->can_buddies_be_invited    = $can_buddies_be_invited;
    }
}
