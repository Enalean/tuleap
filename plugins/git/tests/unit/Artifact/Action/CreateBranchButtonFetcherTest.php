<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Git\Artifact\Action;

use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Layout\IncludeAssetsGeneric;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\ActionButtons\AdditionalButtonAction;

final class CreateBranchButtonFetcherTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testItReturnsNullIfFeatureFlagIsNotSet(): void
    {
        self::assertNull(
            (new CreateBranchButtonFetcher($this->createMock(JavascriptAssetGeneric::class)))->getActionButton()
        );
    }

    public function testItReturnsNullIfFeatureFlagIsFalse(): void
    {
        ForgeConfig::setFeatureFlag(
            CreateBranchButtonFetcher::FEATURE_FLAG_KEY,
            false
        );

        self::assertNull(
            (new CreateBranchButtonFetcher($this->createMock(JavascriptAssetGeneric::class)))->getActionButton()
        );
    }

    public function testItReturnsPresenterIfFeatureFlagIsSetToTrue(): void
    {
        ForgeConfig::setFeatureFlag(
            CreateBranchButtonFetcher::FEATURE_FLAG_KEY,
            true
        );

        $include_asset     = $this->createMock(IncludeAssetsGeneric::class);
        $javascript_assert = new JavascriptAsset(
            $include_asset,
            ""
        );

        $include_asset->method("getFileURL")->willReturn("");

        self::assertInstanceOf(
            AdditionalButtonAction::class,
            (new CreateBranchButtonFetcher($javascript_assert))->getActionButton()
        );
    }
}
