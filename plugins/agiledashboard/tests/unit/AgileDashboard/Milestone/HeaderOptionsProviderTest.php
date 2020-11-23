<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_PaneInfoIdentifier;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class HeaderOptionsProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HeaderOptionsProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new HeaderOptionsProvider(
            new AgileDashboard_PaneInfoIdentifier(),
        );
    }

    public function testGetHeaderOptionsForPV2(): void
    {
        self::assertEquals(
            [
                'include_fat_combined' => false,
                'body_class'           => [
                    'agiledashboard-body',
                    'has-sidebar-with-pinned-header',
                ]
            ],
            $this->provider->getHeaderOptions('planning-v2'),
        );
    }

    public function testGetHeaderOptionsForTopPV2(): void
    {
        self::assertEquals(
            [
                'include_fat_combined' => false,
                'body_class'           => [
                    'agiledashboard-body',
                    'has-sidebar-with-pinned-header',
                ]
            ],
            $this->provider->getHeaderOptions('topplanning-v2'),
        );
    }

    public function testGetHeaderOptionsForOverview(): void
    {
        self::assertEquals(
            [
                'include_fat_combined' => true,
                'body_class'           => [
                    'agiledashboard-body',
                ]
            ],
            $this->provider->getHeaderOptions('details'),
        );
    }
}
