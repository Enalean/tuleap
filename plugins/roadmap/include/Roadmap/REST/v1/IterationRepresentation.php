<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class IterationRepresentation
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $html_url;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $start;
    /**
     * @var string
     */
    public $end;

    public function __construct(
        int $id,
        string $html_url,
        string $title,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
    ) {
        $this->id       = $id;
        $this->html_url = $html_url;
        $this->title    = $title;
        $this->start    = JsonCast::fromNotNullDateTimeToDate($start);
        $this->end      = JsonCast::fromNotNullDateTimeToDate($end);
    }
}
