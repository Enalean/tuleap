<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\Document\Tree;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class SwitchToOldUiTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testItReturnsFalseIfFeatureFlagIsNotSet(): void
    {
        $user    = UserTestBuilder::anActiveUser()->build();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        self::assertFalse(
            SwitchToOldUi::isAllowed($user, $project)
        );
    }

    public function testItReturnsFalseIfFeatureFlagDoesNotContainTheProjectId(): void
    {
        $user    = UserTestBuilder::anActiveUser()->build();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        \ForgeConfig::setFeatureFlag(SwitchToOldUi::FEATURE_FLAG, '102,103,1010');

        self::assertFalse(
            SwitchToOldUi::isAllowed($user, $project)
        );
    }

    public function testItReturnsTrueIfFeatureFlagContainsTheProjectId(): void
    {
        $user    = UserTestBuilder::anActiveUser()->build();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        \ForgeConfig::setFeatureFlag(SwitchToOldUi::FEATURE_FLAG, '102,101,103');

        self::assertTrue(
            SwitchToOldUi::isAllowed($user, $project)
        );
    }

    public function testItReturnsFalseIfFeatureFlagContainsTheProjectIdButUserIsAnonymous(): void
    {
        $user    = UserTestBuilder::anAnonymousUser()->build();
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        \ForgeConfig::setFeatureFlag(SwitchToOldUi::FEATURE_FLAG, '102,101,103');

        self::assertFalse(
            SwitchToOldUi::isAllowed($user, $project)
        );
    }
}
