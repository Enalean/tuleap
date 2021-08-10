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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation;

/**
 * @psalm-immutable
 */
final class StoredProgramIncrementNoLongerValidException extends \Exception
{
    private int $program_increment_id;

    public function __construct(int $program_increment_id)
    {
        parent::__construct(
            sprintf('Artifact #%d is no longer a valid program increment per program configuration', $program_increment_id)
        );
        $this->program_increment_id = $program_increment_id;
    }

    public function getProgramIncrementId(): int
    {
        return $this->program_increment_id;
    }
}
