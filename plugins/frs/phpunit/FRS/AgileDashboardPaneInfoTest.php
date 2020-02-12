<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class AgileDashboardPaneInfoTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var M\LegacyMockInterface|M\MockInterface|\Planning_Milestone */
    private $milestone;
    /** @var int */
    private $release_id = 78;
    /** @var AgileDashboardPaneInfo */
    private $agile_dashboard_pane_info;

    protected function setUp(): void
    {
        $this->milestone                 = M::mock(\Planning_Milestone::class);
        $this->agile_dashboard_pane_info = new AgileDashboardPaneInfo($this->milestone, $this->release_id);
    }

    public function testGetUri(): void
    {
        $this->assertSame('/frs/release/78/release-notes', $this->agile_dashboard_pane_info->getUri());
    }

    public function testGetIconName(): void
    {
        $this->assertSame('fa-files-o', $this->agile_dashboard_pane_info->getIconName());
    }

    public function testIsExternalLink(): void
    {
        $this->assertTrue($this->agile_dashboard_pane_info->isExternalLink());
    }
}
