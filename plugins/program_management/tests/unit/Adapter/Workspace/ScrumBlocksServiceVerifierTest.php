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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\AgileDashboard\Stub\RetrievePlanningStub;
use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\PHPUnit\TestCase;

final class ScrumBlocksServiceVerifierTest extends TestCase
{
    private RetrieveUserStub $user_retriever;
    private UserIdentifier $user;
    private ProjectIdentifier $project;

    protected function setUp(): void
    {
        $this->user_retriever = RetrieveUserStub::withGenericUser();
        $this->user           = UserIdentifierStub::buildGenericUser();
        $this->project        = ProjectIdentifierStub::buildWithId(101);
    }

    public function testItBlocsServiceWhenScrumBlocksUsage(): void
    {
        $retrieve_plannings = RetrievePlanningStub::stubAllPlannings();
        $verifier           = new ScrumBlocksServiceVerifier($retrieve_plannings, $this->user_retriever);

        self::assertTrue($verifier->doesScrumBlockServiceUsage($this->user, $this->project));
    }

    public function testItDoesNotBlockService(): void
    {
        $retrieve_plannings = RetrievePlanningStub::stubNoPlannings();
        $verifier           = new ScrumBlocksServiceVerifier($retrieve_plannings, $this->user_retriever);

        self::assertFalse($verifier->doesScrumBlockServiceUsage($this->user, $this->project));
    }
}
