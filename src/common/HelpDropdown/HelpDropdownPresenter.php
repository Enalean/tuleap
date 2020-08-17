<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
declare(strict_types=1);

namespace Tuleap\HelpDropdown;

/**
 * @psalm-immutable
 */
class HelpDropdownPresenter
{
    /**
     * @var HelpLinkPresenter[]
     */
    public $platform_links;
    /**
     * @var HelpLinkPresenter|null
     */
    public $release_note_link;
    /**
     * @var string|null
     */
    public $explorer_url;

    public function __construct(
        array $platform_links,
        ?string $explorer_url,
        ?HelpLinkPresenter $release_note_link
    ) {
        $this->platform_links    = $platform_links;
        $this->release_note_link = $release_note_link;
        $this->explorer_url      = $explorer_url;
    }
}
