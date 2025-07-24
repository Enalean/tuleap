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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\RetrieveLastChangeset;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;

final class RetrieveLastChangesetStub implements RetrieveLastChangeset
{
    private bool $should_return_null;
    /**
     * @var int[]
     */
    private array $last_changeset_ids;

    private function __construct(bool $should_return_null, int ...$last_changeset_ids)
    {
        $this->should_return_null = $should_return_null;
        $this->last_changeset_ids = $last_changeset_ids;
    }

    #[\Override]
    public function retrieveLastChangesetId(TimeboxIdentifier $timebox_identifier): ?int
    {
        if ($this->should_return_null) {
            return null;
        }
        if (count($this->last_changeset_ids) > 0) {
            return array_shift($this->last_changeset_ids);
        }
        throw new \LogicException('No changeset id configured');
    }

    public static function withLastChangesetIds(int ...$last_changeset_ids): self
    {
        return new self(false, ...$last_changeset_ids);
    }

    public static function withNoLastChangeset(): self
    {
        return new self(true);
    }
}
