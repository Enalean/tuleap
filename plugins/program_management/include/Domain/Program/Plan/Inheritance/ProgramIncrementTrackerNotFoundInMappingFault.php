<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance;

use Tuleap\NeverThrow\Fault;

/**
 * @psalm-immutable
 */
final readonly class ProgramIncrementTrackerNotFoundInMappingFault extends Fault
{
    public static function build(
        int $source_program_id,
        int $new_program_id,
        int $source_program_increment_tracker_id,
    ): Fault {
        return new self(
            sprintf(
                'Could not find mapping for source Program Increment tracker #%1$d while inheriting from Program #%2$d to new Program #%3$d',
                $source_program_increment_tracker_id,
                $source_program_id,
                $new_program_id
            )
        );
    }
}
