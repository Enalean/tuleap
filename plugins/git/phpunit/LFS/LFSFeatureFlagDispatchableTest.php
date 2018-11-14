<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\LFS;

use HTTPRequest;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class LFSFeatureFlagDispatchableTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp()
    {
        \ForgeConfig::store();
    }

    protected function tearDown()
    {
        \ForgeConfig::restore();
    }

    public function testProcessRequestIfFeatureFlagIsEnabled()
    {
        \ForgeConfig::set('git_lfs_dev_enable', 1);

        $dispatchable = \Mockery::mock(DispatchableWithRequestNoAuthz::class);

        $dispatchable->shouldReceive('process')->once();

        $lfs_feature_flag_dispatchable = new LFSFeatureFlagDispatchable($dispatchable);
        $lfs_feature_flag_dispatchable->process(
            \Mockery::mock(HTTPRequest::class),
            \Mockery::mock(BaseLayout::class),
            []
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testProcessRequestIsIgnoredIfFeatureFlagIsNotEnabled()
    {
        $dispatchable = \Mockery::mock(DispatchableWithRequestNoAuthz::class);

        $dispatchable->shouldReceive('process')->never();

        $lfs_feature_flag_dispatchable = new LFSFeatureFlagDispatchable($dispatchable);
        $lfs_feature_flag_dispatchable->process(
            \Mockery::mock(HTTPRequest::class),
            \Mockery::mock(BaseLayout::class),
            []
        );
        ob_clean();
    }
}
