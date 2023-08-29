<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Kanban;

use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class CheckSplitKanbanConfigurationTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testFalseWhenFeatureFlagIsNotSetAtAll(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $checker = new CheckSplitKanbanConfiguration();

        self::assertFalse($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testFalseWhenFeatureFlagIsSetTo0(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '0');

        $checker = new CheckSplitKanbanConfiguration();

        self::assertFalse($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testFalseWhenProjectIdIsNotPartOfFeatureFlag(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '123,456');

        $checker = new CheckSplitKanbanConfiguration();

        self::assertFalse($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testTrueWhenProjectIdIsPartOfFeatureFlag(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(123)->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '123,456');

        $checker = new CheckSplitKanbanConfiguration();

        self::assertTrue($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testTrueWhenFeatureFlagIsSetForAllProjects(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(123)->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '1');

        $checker = new CheckSplitKanbanConfiguration();

        self::assertTrue($checker->isProjectAllowedToUseSplitKanban($project));
    }
}
