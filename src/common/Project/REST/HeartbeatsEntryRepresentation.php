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

namespace Tuleap\Project\REST;

use Tuleap\Project\HeartbeatsEntry;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
class HeartbeatsEntryRepresentation
{
    /**
     * @var string Date of the last update of this entry {@type int} {@required true}
     */
    public $updated_at;
    /**
     * @var string Title of the entry {@type string} {@required true}
     */
    public $html_message;
    /**
     * @var string SVG icon associated with the entry {@type string} {@required true}
     */
    public $icon;
    /**
     * @var string SVG icon (small size) associated with the entry {@type string} {@required true}
     */
    public $small_icon;

    private function __construct(string $updated_at, string $html_message, string $icon, string $small_icon)
    {
        $this->updated_at   = $updated_at;
        $this->html_message = $html_message;
        $this->icon         = $icon;
        $this->small_icon   = $small_icon;
    }

    public static function build(HeartbeatsEntry $entry): self
    {
        return new self(
            JsonCast::toDate($entry->getUpdatedAt()),
            $entry->getHTMLMessage(),
            $entry->getNormalIcon()->getInlineString(),
            $entry->getSmallIcon()->getInlineString()
        );
    }
}
