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

namespace Tuleap\ProgramManagement\REST\v1;

use Tuleap\ProgramManagement\Program\Backlog\ProgramIncrement\ProgramIncrement;
use Tuleap\REST\JsonCast;

/**
 * @psalm-immutable
 */
final class ProgramIncrementRepresentation
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
     * @var string | null
     */
    public $status;
    /**
     * @var string | null {@type date}{@required false}
     */
    public $start_date;
    /**
     * @var string | null {@type date}{@required false}
     */
    public $end_date;
    /**
     * @var bool {@type bool}
     */
    public $user_can_update;
    /**
     * @var bool {@type bool}
     */
    public $user_can_plan;

    private function __construct(
        int $id,
        string $title,
        bool $user_can_update,
        bool $user_can_plan,
        ?string $status,
        ?int $start_date,
        ?int $end_date
    ) {
        $this->id              = $id;
        $this->title           = $title;
        $this->status          = $status;
        $this->start_date      = JsonCast::toDate($start_date);
        $this->end_date        = JsonCast::toDate($end_date);
        $this->user_can_update = JsonCast::toBoolean($user_can_update);
        $this->user_can_plan   = JsonCast::toBoolean($user_can_plan);
    }

    public static function fromProgramIncrement(ProgramIncrement $program_increment): self
    {
        return new self(
            $program_increment->id,
            $program_increment->title,
            $program_increment->user_can_update,
            $program_increment->user_can_plan,
            $program_increment->status,
            $program_increment->start_date,
            $program_increment->end_date
        );
    }
}
