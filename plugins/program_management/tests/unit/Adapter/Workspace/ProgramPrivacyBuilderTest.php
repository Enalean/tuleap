<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

use Tuleap\ForgeConfigSandbox;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramPrivacy;
use Tuleap\ProgramManagement\Tests\Builder\ProgramIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\Test\Builders\ProjectTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramPrivacyBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItBuildsProgramPrivacy(): void
    {
        \ForgeConfig::set(\ForgeAccess::CONFIG, \ForgeAccess::REGULAR);

        $project = ProjectTestBuilder::aProject()
            ->withId(101)
            ->withAccess(\Project::ACCESS_PUBLIC)
            ->build();

        $builder = new ProgramPrivacyBuilder(RetrieveFullProjectStub::withProject($project));

        $program_privacy = $builder->build(
            ProgramIdentifierBuilder::buildWithId(101)
        );

        self::assertEquals(
            ProgramPrivacy::fromPrivacy(
                false,
                false,
                false,
                true,
                false,
                'Project privacy set to public. By default, its content is available to all authenticated, but not restricted, users. Please note that more restrictive permissions might exist on some items.',
                'Public',
                'The Test Project'
            ),
            $program_privacy
        );
    }
}
