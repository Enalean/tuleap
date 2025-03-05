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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Icon;

use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Icons\InvalidProjectIconException;
use Tuleap\Project\Icons\ProjectIconChecker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class ProjectIconCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    public function testItThrowsExceptionIfTheGivenIconIsAForbiddenIcon(): void
    {
        ForgeConfig::set('feature_flag_project_icon_display', '1');
        self::expectException(InvalidProjectIconException::class);

        ProjectIconChecker::isIconValid('✖️');
    }

    public function testItDoesNothingIfTheFeatureIsDisabled(): void
    {
        ForgeConfig::set('feature_flag_project_icon_display', '0');

        ProjectIconChecker::isIconValid('😇');
        self::expectNotToPerformAssertions();
    }

    public function testItDoesNothingIfTheIconIsValid(): void
    {
        ForgeConfig::set('feature_flag_project_icon_display', '1');

        ProjectIconChecker::isIconValid('😇');

        self::expectNotToPerformAssertions();
    }

    public function testItDoesNothingIfThereIsNoIcon(): void
    {
        ForgeConfig::set('feature_flag_project_icon_display', '1');

        ProjectIconChecker::isIconValid('');

        self::expectNotToPerformAssertions();
    }
}
