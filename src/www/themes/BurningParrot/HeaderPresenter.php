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

namespace Tuleap\Theme\BurningParrot;

use PFUser;
use Tuleap\Theme\BurningParrot\Navbar\Presenter as NavbarPresenter;

class HeaderPresenter
{
    /** @var string */
    public $title;

    /** @var string */
    public $imgroot;

    /** @var Tuleap\Theme\BurningParrot\Navbar\Presenter */
    public $navbar_presenter;

    /** @var array */
    public $stylesheets;

    public function __construct(
        $title,
        $imgroot,
        NavbarPresenter $navbar_presenter,
        array $stylesheets
    ) {
        $this->title            = html_entity_decode($title);
        $this->imgroot          = $imgroot;
        $this->navbar_presenter = $navbar_presenter;
        $this->stylesheets      = $stylesheets;
    }
}
