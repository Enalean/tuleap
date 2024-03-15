<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

/**
 * @psalm-immutable
 */
final class LinkWithDirectionRepresentation
{
    /**
     * @var int $id {@min 1}
     * @psalm-var int<1,max>
     */
    public int $id;

    /**
     * @var string $direction {@choice reverse,forward}
     * @psalm-var 'reverse'|'forward'
     */
    public string $direction;

    /**
     * @var string | null $type {@required false}
     */
    public ?string $type = null;
}
