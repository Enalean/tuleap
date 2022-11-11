<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\REST\v1;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\Iteration;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class IterationRepresentation
{
    public int $id;
    public string $uri;
    public string $xref;
    public ?string $title;
    public ?string $status;
    /**
     * @var string | null {@type date}{@required false}
     */
    public ?string $start_date;
    /**
     * @var string | null {@type date}{@required false}
     */
    public ?string $end_date;
    /**
     * @var bool {@type bool}
     */
    public bool $user_can_update;

    private function __construct(
        int $id,
        string $title,
        string $uri,
        string $xref,
        bool $user_can_update,
        ?string $status,
        ?int $start_date,
        ?int $end_date,
    ) {
        $this->id              = $id;
        $this->uri             = $uri;
        $this->xref            = $xref;
        $this->title           = $title;
        $this->status          = $status;
        $this->start_date      = JsonCast::toDate($start_date);
        $this->end_date        = JsonCast::toDate($end_date);
        $this->user_can_update = JsonCast::toBoolean($user_can_update);
    }

    public static function buildFromIteration(
        Iteration $iteration,
    ): self {
        return new self(
            $iteration->id,
            $iteration->title,
            $iteration->uri,
            $iteration->cross_ref,
            $iteration->user_can_update,
            $iteration->status,
            $iteration->start_date,
            $iteration->end_date
        );
    }
}
