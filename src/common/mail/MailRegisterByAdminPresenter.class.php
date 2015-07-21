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

class MailRegisterByAdminPresenter extends MailRegisterPresenter {

    public $section_three;
    public $section_after_password;
    public $password;

    public function __construct(
        $logo_url,
        $title,
        $section_one,
        $section_two,
        $section_after_login,
        $thanks,
        $signature,
        $help,
        $color_logo,
        $login,
        $section_three,
        $section_after_password,
        $password
    ) {
        parent::__construct($logo_url, $title, $section_one, $section_two, $section_after_login, $thanks, $signature, $help, $color_logo, $login);
        $this->section_three          = $section_three;
        $this->section_after_password = $section_after_password;
        $this->password               = $password;
    }
}
?>