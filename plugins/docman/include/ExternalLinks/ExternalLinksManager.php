<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\ExternalLinks;

final class ExternalLinksManager
{
    /**
     * @var Link[]
     */
    private $external_links = [];

    public function addExternalLink(Link $external_link): void
    {
        $this->external_links[] = $external_link;
    }

    public function hasExternalLinks(): bool
    {
        return count($this->external_links) > 0;
    }

    /**
     * @return Link[]
     */
    public function getLinks(): array
    {
        return $this->external_links;
    }
}
