<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Banner;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class BannerRemoverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBannerIsRemoved(): void
    {
        $dao            = \Mockery::mock(BannerDao::class);
        $banner_remover = new BannerRemover($dao);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturn('102');

        $dao->shouldReceive('deleteBanner')->once();

        $banner_remover->deleteBanner(new UserCanEditBannerPermission($project));
    }
}
