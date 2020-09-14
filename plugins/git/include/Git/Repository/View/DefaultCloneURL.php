<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

class DefaultCloneURL
{
    /** @var string */
    private $url;
    /** @var string */
    private $label;
    /**
     * @var string
     */
    private $id;

    public function __construct(string $id, string $url, string $label)
    {
        $this->id    = $id;
        $this->url   = $url;
        $this->label = $label;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function hasSameUrl(string $url): bool
    {
        return $this->url === $url;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
