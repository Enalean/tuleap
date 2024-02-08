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

namespace Tuleap\Git\REST\v1;

/**
 * @psalm-immutable
 */
final class ReferenceRepresentation
{
    public string $ref;
    public string $url;
    public string $direction;

    private function __construct(string $ref, string $url, string $direction)
    {
        $this->ref       = $ref;
        $this->url       = $url;
        $this->direction = $direction;
    }

    public static function inReferenceRepresentation(string $ref, string $url): ReferenceRepresentation
    {
        return new self($ref, $url, 'in');
    }

    public static function outReferenceRepresentation(string $ref, string $url): ReferenceRepresentation
    {
        return new self($ref, $url, 'out');
    }

    public static function bothReferenceRepresentation(string $ref, string $url): ReferenceRepresentation
    {
        return new self($ref, $url, 'both');
    }
}
