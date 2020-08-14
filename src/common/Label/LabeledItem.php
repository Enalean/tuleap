<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Label;

use Tuleap\Glyph\Glyph;

/**
 * @psalm-immutable
 */
class LabeledItem
{
    /**
     * @var string
     */
    private $title;
    /**
     * @var Glyph
     */
    private $normal_icon;
    /**
     * @var Glyph
     */
    private $small_icon;
    /**
     * @var string URL to access the item through the Web UI
     */
    private $html_url;

    public function __construct(
        Glyph $normal_icon,
        Glyph $small_icon,
        $title,
        $html_url
    ) {
        $this->normal_icon = $normal_icon;
        $this->small_icon  = $small_icon;
        $this->title       = $title;
        $this->html_url    = $html_url;
    }

    /**
     * @return string
     */
    public function getHtmlUrl()
    {
        return $this->html_url;
    }

    public function getNormalIcon()
    {
        return $this->normal_icon;
    }

    public function getSmallIcon()
    {
        return $this->small_icon;
    }

    public function getTitle()
    {
        return $this->title;
    }
}
