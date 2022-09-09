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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Project;

final class SynchronizedProjectMembershipDuplicatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var SynchronizedProjectMembershipDuplicator
     */
    private $duplicator;
    /**
     * @var M\MockInterface|SynchronizedProjectMembershipDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao        = M::mock(SynchronizedProjectMembershipDao::class);
        $this->duplicator = new SynchronizedProjectMembershipDuplicator($this->dao);
    }

    public function testDuplicateSucceeds(): void
    {
        $destination = M::mock(Project::class);
        $destination->shouldReceive('isPublic')->andReturnTrue();
        $destination->shouldReceive('getID')->andReturn(120);

        $this->dao->shouldReceive('duplicateActivationFromTemplate')
            ->with(104, 120)->atLeast()->once();

        $this->duplicator->duplicate(104, $destination);
    }

    public function testDuplicateDoesNothingWhenTheDestinationProjectIsPrivate(): void
    {
        $destination = M::mock(Project::class);
        $destination->shouldReceive('isPublic')->andReturnFalse();

        $this->dao->shouldNotReceive('duplicateActivationFromTemplate');

        $this->duplicator->duplicate(104, $destination);
    }
}
