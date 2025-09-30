<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\REST\v1;

/**
 * @psalm-immutable
 */
final class OrderRepresentation
{
    /**
     * @var array List of section identifier {@type string}
     */
    public $ids;

    /**
     * @var string before|after
     */
    public $direction;

    /**
     * @var string Section identifier
     */
    public $compared_to;

    /**
     * @param string[] $ids
     */
    private function __construct(
        array $ids,
        string $direction,
        string $compared_to,
    ) {
        $this->ids         = $ids;
        $this->direction   = $direction;
        $this->compared_to = $compared_to;
    }

    /**
     * @param string[] $ids
     */
    public static function build(
        array $ids,
        string $direction,
        string $compared_to,
    ): self {
        return new self($ids, $direction, $compared_to);
    }
}
