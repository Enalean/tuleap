<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Docman\Item;

class OpenItemHref implements \Tuleap\Event\Dispatchable
{
    public const NAME     = 'openItemHref';
    private ?string $href = null;

    public function __construct(private \Docman_File $item, private \Docman_Version $version)
    {
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function setHref(string $href): void
    {
        $this->href = $href;
    }

    public function getItem(): \Docman_File
    {
        return $this->item;
    }

    public function getVersion(): \Docman_Version
    {
        return $this->version;
    }
}
