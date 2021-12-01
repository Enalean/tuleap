<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\JSON;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Iteration\IterationUpdate;

/**
 * @psalm-immutable
 */
final class PendingIterationUpdateRepresentation
{
    private function __construct(
        public int $iteration_id,
        public int $user_id,
        public int $changeset_id,
    ) {
    }

    public static function fromIterationUpdate(IterationUpdate $iteration_update): self
    {
        return new self(
            $iteration_update->getIteration()->getId(),
            $iteration_update->getUser()->getId(),
            $iteration_update->getChangeset()->getId()
        );
    }
}
