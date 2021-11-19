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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\Feature;

use Tuleap\ProgramManagement\Adapter\Permissions\WorkflowUserPermissionBypass;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleByProgramStub;
use Tuleap\ProgramManagement\Tests\Stub\VerifyFeatureIsVisibleStub;

final class FeatureIdentifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserIdentifierStub $user;
    private ProgramIdentifier $program;

    protected function setUp(): void
    {
        $this->user    = UserIdentifierStub::buildGenericUser();
        $this->program = ProgramIdentifierBuilder::build();
    }

    public function testItReturnsNullWhenProgramFeatureIsNotVisibleByUser(): void
    {
        self::assertNull(
            FeatureIdentifier::fromIdAndProgram(
                VerifyFeatureIsVisibleByProgramStub::withNotVisibleFeature(),
                404,
                $this->user,
                $this->program,
                null
            )
        );
    }

    public function testItBuildsAFeatureVisibleByProgram(): void
    {
        $feature = FeatureIdentifier::fromIdAndProgram(
            VerifyFeatureIsVisibleByProgramStub::buildVisibleFeature(),
            87,
            $this->user,
            $this->program,
            null
        );
        self::assertNotNull($feature);
        self::assertSame(87, $feature->id);
        self::assertSame(87, $feature->getId());
    }

    public function testItBuildsAValidProgramFeatureWithPermissionBypass(): void
    {
        $feature = FeatureIdentifier::fromIdAndProgram(
            VerifyFeatureIsVisibleByProgramStub::buildVisibleFeature(),
            5,
            $this->user,
            $this->program,
            new WorkflowUserPermissionBypass()
        );
        self::assertNotNull($feature);
        self::assertSame(5, $feature->id);
        self::assertSame(5, $feature->getId());
    }

    public function testItBuildsAVisibleFeature(): void
    {
        $feature = FeatureIdentifier::fromId(
            VerifyFeatureIsVisibleStub::buildVisibleFeature(),
            5,
            $this->user
        );
        self::assertNotNull($feature);
        self::assertSame(5, $feature->id);
        self::assertSame(5, $feature->getId());
    }

    public function testItReturnsNullWhenFeatureIsNotVisible(): void
    {
        $feature = FeatureIdentifier::fromId(
            VerifyFeatureIsVisibleStub::withNotVisibleFeature(),
            5,
            $this->user
        );
        self::assertNull($feature);
    }
}
