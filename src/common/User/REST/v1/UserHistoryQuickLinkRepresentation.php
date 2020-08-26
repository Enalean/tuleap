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

namespace Tuleap\User\REST\v1;

use Tuleap\User\History\HistoryQuickLink;

/**
 * @psalm-immutable
 */
class UserHistoryQuickLinkRepresentation
{
    /**
     * @var string Name of the quick link {@type string} {@required true}
     */
    public $name;
    /**
     * @var string Link to the information {@type string} {@required true}
     */
    public $html_url;
    /**
     * @var string Icon for the quick link {@type string} {@required true}
     */
    public $icon_name;

    private function __construct(string $name, string $html_url, string $icon_name)
    {
        $this->name      = $name;
        $this->html_url  = $html_url;
        $this->icon_name = $icon_name;
    }

    public static function build(HistoryQuickLink $quick_link): self
    {
        return new self(
            $quick_link->getName(),
            $quick_link->getUrl(),
            $quick_link->getIconName()
        );
    }
}
