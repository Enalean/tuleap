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
use Tuleap\Test\Stubs\EventDispatcherStub;

final class CheckSplitKanbanConfigurationTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testTrueWhenFeatureFlagIsNotSetAtAll(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $checker = new CheckSplitKanbanConfiguration(EventDispatcherStub::withIdentityCallback());

        self::assertTrue($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testTrueWhenFeatureFlagIsSetTo0(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '0');

        $checker = new CheckSplitKanbanConfiguration(EventDispatcherStub::withIdentityCallback());

        self::assertTrue($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testTrueWhenProjectIdIsNotPartOfFeatureFlag(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '123,456');

        $checker = new CheckSplitKanbanConfiguration(EventDispatcherStub::withIdentityCallback());

        self::assertTrue($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testFalseWhenProjectIdIsPartOfFeatureFlag(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(123)->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '123,456');

        $checker = new CheckSplitKanbanConfiguration(EventDispatcherStub::withIdentityCallback());

        self::assertFalse($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testFalseWhenFeatureFlagIsSetForAllProjects(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(123)->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '1');

        $checker = new CheckSplitKanbanConfiguration(EventDispatcherStub::withIdentityCallback());

        self::assertFalse($checker->isProjectAllowedToUseSplitKanban($project));
    }

    public function testPluginsCanForceUsageOfSplitKanbanEvenIfFeatureFlagDeactivateForTheProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(123)->build();

        \ForgeConfig::setFeatureFlag(SplitKanbanConfiguration::FEATURE_FLAG, '1');

        $checker = new CheckSplitKanbanConfiguration(EventDispatcherStub::withCallback(function (ForceUsageOfSplitKanbanEvent $event): object {
            $event->splitKanbanIsMandatory();
            return $event;
        }));

        self::assertTrue($checker->isProjectAllowedToUseSplitKanban($project));
    }
}
