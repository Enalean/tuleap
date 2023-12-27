<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\UGroups;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class SynchronizedProjectMembershipDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private SynchronizedProjectMembershipDuplicator $duplicator;
    private SynchronizedProjectMembershipDao&MockObject $dao;

    protected function setUp(): void
    {
        $this->dao        = $this->createMock(SynchronizedProjectMembershipDao::class);
        $this->duplicator = new SynchronizedProjectMembershipDuplicator($this->dao);
    }

    public function testDuplicateSucceeds(): void
    {
        $destination = ProjectTestBuilder::aProject()
            ->withId(120)
            ->withAccessPublic()
            ->build();

        $this->dao->expects(self::atLeastOnce())->method('duplicateActivationFromTemplate')
            ->with(104, 120);

        $this->duplicator->duplicate(104, $destination);
    }

    public function testDuplicateDoesNothingWhenTheDestinationProjectIsPrivate(): void
    {
        $destination = ProjectTestBuilder::aProject()->withAccessPrivate()->build();

        $this->dao->expects(self::never())->method('duplicateActivationFromTemplate');

        $this->duplicator->duplicate(104, $destination);
    }
}
