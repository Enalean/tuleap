<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\CommitStatus;

use DateTimeImmutable;
use DomainException;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommitStatusWithKnownStatusTest extends TestCase
{
    private readonly DateTimeImmutable $date;

    #[\Override]
    protected function setUp(): void
    {
        $this->date = new DateTimeImmutable();
    }

    public function testCanBeBuiltFromStatusName(): void
    {
        $status_name = 'success';

        $commit_status = CommitStatusWithKnownStatus::buildFromStatusName($status_name, $this->date);

        self::assertEquals($status_name, $commit_status->getStatusName());
    }

    public function testInvalidStatusNameIsRejectedWhenBuilding(): void
    {
        $this->expectException(DomainException::class);

        CommitStatusWithKnownStatus::buildFromStatusName('invalid_status_name', $this->date);
    }

    public function testInvalidStatusIdIsRejectedWhenBuilding(): void
    {
        $this->expectException(DomainException::class);

        new CommitStatusWithKnownStatus(999999999999999, $this->date);
    }
}
