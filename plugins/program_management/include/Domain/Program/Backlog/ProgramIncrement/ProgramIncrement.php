<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement;

/**
 * @psalm-immutable
 */
final class ProgramIncrement
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $title;

    /**
     * @var string|null
     */
    public $status;

    /**
     * @var int|null
     */
    public $start_date;
    /**
     * @var int|null
     */
    public $end_date;
    /**
     * @var bool
     */
    public $user_can_update;
    /**
     * @var bool
     */
    public $user_can_plan;
    /**
     * @var string
     */
    public $uri;
    /**
     * @var string
     */
    public $xref;

    public function __construct(
        int $id,
        string $title,
        string $uri,
        string $xref,
        bool $user_can_update,
        bool $user_can_plan,
        ?string $status,
        ?int $start_date,
        ?int $end_date
    ) {
        $this->title           = $title;
        $this->status          = $status;
        $this->start_date      = $start_date;
        $this->end_date        = $end_date;
        $this->id              = $id;
        $this->user_can_update = $user_can_update;
        $this->user_can_plan   = $user_can_plan;
        $this->uri             = $uri;
        $this->xref            = $xref;
    }
}
