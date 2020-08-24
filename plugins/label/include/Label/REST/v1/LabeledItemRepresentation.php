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

namespace Tuleap\Label\REST\v1;

use Tuleap\Label\LabeledItem;

/**
 * @psalm-immutable
 */
class LabeledItemRepresentation
{
    /**
     * @var string Title of the item {@type string} {@required true}
     */
    public $title;

    /**
     * @var string SVG icon associated with the item {@type string} {@required true}
     */
    public $icon;

    /**
     * @var string SVG icon (small size) associated with the item {@type string} {@required true}
     */
    public $small_icon;

    /**
     * @var string Link to the entry in the Web UI {@type string} {@required true}
     */
    public $html_url;

    public function __construct(LabeledItem $item)
    {
        $this->title      = $item->getTitle();
        $this->html_url   = $item->getHtmlUrl();
        $this->icon       = $item->getNormalIcon()->getInlineString();
        $this->small_icon = $item->getSmallIcon()->getInlineString();
    }
}
