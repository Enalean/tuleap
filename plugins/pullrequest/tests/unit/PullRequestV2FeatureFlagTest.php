<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use Tuleap\ForgeConfigSandbox;

final class PullRequestV2FeatureFlagTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const PROJECT_ID = 120;

    public function testPullRequestV2CanBeDisplayedWhenTheFeatureFlagIsNotSet(): void
    {
        self::assertTrue(PullRequestV2FeatureFlag::isPullRequestV2Displayed($this->getMockedRepository()));
    }

    public function testPullRequestV2CanBeDisplayedWhenTheFeatureFlagValueIsZero(): void
    {
        $this->setFeatureFlag("0");

        self::assertTrue(PullRequestV2FeatureFlag::isPullRequestV2Displayed($this->getMockedRepository()));
    }

    public function testPullRequestV2CanBeDisplayedWhenTheFeatureIsNotDisabledForTheCurrentProject(): void
    {
        $this->setFeatureFlag("101, 106");

        self::assertTrue(PullRequestV2FeatureFlag::isPullRequestV2Displayed($this->getMockedRepository()));
    }

    public function testPullRequestV2CannotBeDisplayedWhenTheFeatureIsDisabledForTheCurrentProject(): void
    {
        $this->setFeatureFlag((string) self::PROJECT_ID);

        self::assertFalse(PullRequestV2FeatureFlag::isPullRequestV2Displayed($this->getMockedRepository()));
    }

    public function testPullRequestV2CannotBeDisplayedWhenTheFeatureFlagValueIsOne(): void
    {
        $this->setFeatureFlag("1");

        self::assertFalse(PullRequestV2FeatureFlag::isPullRequestV2Displayed($this->getMockedRepository()));
    }

    private function getMockedRepository(): \GitRepository
    {
        $repository = $this->createMock(\GitRepository::class);
        $repository->method("getProjectId")->willReturn(self::PROJECT_ID);

        return $repository;
    }

    private function setFeatureFlag(string $value): void
    {
        \ForgeConfig::set("feature_flag_" . PullRequestV2FeatureFlag::FEATURE_FLAG_KEY, $value);
    }
}
