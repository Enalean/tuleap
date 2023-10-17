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
use Tuleap\Test\PHPUnit\TestCase;

final class FeatureFlagEditCommentsTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testItReturnsTrueWhenTheFeatureIsEnabled(): void
    {
        \ForgeConfig::setFeatureFlag(FeatureFlagEditComments::FEATURE_FLAG_KEY, "1");

        self::assertTrue(FeatureFlagEditComments::isCommentEditionEnabled());
    }

    public function testItReturnsFalseWhenTheFeatureIsDisabled(): void
    {
        \ForgeConfig::setFeatureFlag(FeatureFlagEditComments::FEATURE_FLAG_KEY, "0");

        self::assertFalse(FeatureFlagEditComments::isCommentEditionEnabled());
    }

    public function testItReturnsFalseWhenTheFeatureFlagKeyDoesNotExistInForgeConfig(): void
    {
        self::assertFalse(FeatureFlagEditComments::isCommentEditionEnabled());
    }
}
