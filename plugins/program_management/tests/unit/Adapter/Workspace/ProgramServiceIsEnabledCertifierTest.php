<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\ProgramServiceIsEnabledCertificate;
use Tuleap\ProgramManagement\ProgramService;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramServiceIsEnabledCertifierTest extends TestCase
{
    private const PROJECT_ID = 114;
    private \Project $project;


    /** @return Option<ProgramServiceIsEnabledCertificate> */
    private function certify(): Option
    {
        $certifier = new ProgramServiceIsEnabledCertifier();
        return $certifier->certifyProgramServiceEnabled($this->project);
    }

    public function testItReturnsNothingWhenProgramServiceIsNotUsedInProject(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->withoutServices()->build();

        self::assertTrue($this->certify()->isNothing());
    }

    public function testItReturnsCertificateWhenProgramServiceIsUsedInProject(): void
    {
        $this->project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)
            ->withUsedService(ProgramService::SERVICE_SHORTNAME)
            ->build();

        self::assertSame(
            $this->certify()->mapOr(
                static fn(ProgramServiceIsEnabledCertificate $certification) => $certification->program_id,
                0
            ),
            self::PROJECT_ID
        );
    }
}
