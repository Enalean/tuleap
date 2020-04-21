<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

namespace Tuleap\Git\CommitStatus;

require_once __DIR__ . '/../bootstrap.php';

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CommitStatusWithKnownStatusTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testCanBeBuiltFromStatusName()
    {
        $status_name = 'success';
        $date        = \Mockery::mock(\DateTimeImmutable::class);

        $commit_status = CommitStatusWithKnownStatus::buildFromStatusName($status_name, $date);

        $this->assertEquals($status_name, $commit_status->getStatusName());
    }

    public function testInvalidStatusNameIsRejectedWhenBuilding()
    {
        $date = \Mockery::mock(\DateTimeImmutable::class);

        $this->expectException(\DomainException::class);

        CommitStatusWithKnownStatus::buildFromStatusName('invalid_status_name', $date);
    }

    public function testInvalidStatusIdIsRejectedWhenBuilding()
    {
        $date = \Mockery::mock(\DateTimeImmutable::class);

        $this->expectException(\DomainException::class);

        new CommitStatusWithKnownStatus(999999999999999, $date);
    }
}
