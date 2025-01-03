<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

class MailRegisterByAdminNotificationPresenter extends MailNotificationPresenter
{
    public $section_two;
    public $login;
    public $section_after_login;

    public function __construct(
        string $logo_url,
        $title,
        $section_one,
        $section_two,
        $thanks,
        $signature,
        $color_logo,
        $redirect_url,
        $redirect_button,
        $color_button,
        $login,
        $section_after_login,
    ) {
        parent::__construct($logo_url, $title, $section_one, $thanks, $signature, $color_logo, $redirect_url, $redirect_button, $color_button);
        $this->section_two         = $section_two;
        $this->login               = $login;
        $this->section_after_login = $section_after_login;
    }
}
