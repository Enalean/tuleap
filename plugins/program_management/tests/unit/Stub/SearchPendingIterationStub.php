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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\SearchPendingIteration;

final class SearchPendingIterationStub implements SearchPendingIteration
{
    private ?array $values;

    /**
     * @param null|array{iteration_id: int, program_increment_id: int, user_id: int, iteration_changeset_id: int} $values
     */
    private function __construct(?array $values)
    {
        $this->values = $values;
    }

    public function searchPendingIterationCreation(int $iteration_id, int $user_id): ?array
    {
        if ($this->values === null) {
            return null;
        }
        if ($iteration_id !== $this->values['iteration_id'] || $user_id !== $this->values['user_id']) {
            return null;
        }
        return $this->values;
    }

    public static function withRow(
        int $iteration_id,
        int $program_increment_id,
        int $user_id,
        int $iteration_changeset_id
    ): self {
        return new self(
            [
                'iteration_id'           => $iteration_id,
                'program_increment_id'   => $program_increment_id,
                'user_id'                => $user_id,
                'iteration_changeset_id' => $iteration_changeset_id
            ]
        );
    }

    public static function withNoRow(): self
    {
        return new self(null);
    }
}
