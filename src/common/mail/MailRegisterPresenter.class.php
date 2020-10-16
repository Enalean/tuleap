<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class MailRegisterPresenter extends MailOutlinePresenter
{

    public $section_one;
    public $section_two;
    public $section_after_login;
    public $help;
    public $login;

    public function __construct(
        string $logo_url,
        $title,
        $section_one,
        $section_two,
        $section_after_login,
        $thanks,
        $signature,
        $help,
        $color_logo,
        $login
    ) {
        parent::__construct($logo_url, $title, $thanks, $signature, $color_logo);
        $this->section_one          = $section_one;
        $this->section_two          = $section_two;
        $this->section_after_login  = $section_after_login;
        $this->help                 = $help;
        $this->login                = $login;
    }
}
