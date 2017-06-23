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

namespace Tuleap\BotMattermostGit\SenderServices;


class Attachment
{
    const COLOR_BLUE = '#36a64f';

    private $pre_text;
    private $title;
    private $title_link;
    private $text;
    private $color;

    public function __construct($pre_text, $title, $title_link, $text)
    {
        $this->pre_text   = $pre_text;
        $this->title      = $title;
        $this->title_link = $title_link;
        $this->text       = $text;
        $this->color      = self::COLOR_BLUE;
    }

    public function getPreText()
    {
        return $this->pre_text;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getTitleLink()
    {
        return $this->title_link;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getColor()
    {
        return $this->color;
    }
}