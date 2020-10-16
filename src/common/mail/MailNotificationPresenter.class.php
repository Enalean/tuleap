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

class MailNotificationPresenter extends MailOutlinePresenter
{

    public $section_one;
    public $redirect_url;
    public $redirect_button;
    public $color_button;

    public function __construct(
        string $logo_url,
        $title,
        $section_one,
        $thanks,
        $signature,
        $color_logo,
        $redirect_url,
        $redirect_button,
        $color_button
    ) {
        parent::__construct($logo_url, $title, $thanks, $signature, $color_logo);
        $this->section_one          = $section_one;
        $this->redirect_url         = $redirect_url;
        $this->redirect_button      = $redirect_button;
        $this->color_button         = $color_button;
    }
}
