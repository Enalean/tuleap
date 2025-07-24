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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Timebox\RetrieveCrossRef;
use Tuleap\ProgramManagement\Domain\Program\Backlog\TimeboxIdentifier;

final class RetrieveCrossRefStub implements RetrieveCrossRef
{
    private function __construct(private string $cross_reference)
    {
    }

    public static function withDefault(): self
    {
        return new self('art #1');
    }

    public static function withValues(string $tracker_shortname, int $id): self
    {
        return new self($tracker_shortname . ' #' . $id);
    }

    #[\Override]
    public function getXRef(TimeboxIdentifier $timebox_identifier): string
    {
        return $this->cross_reference;
    }
}
