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


namespace Tuleap\Tracker\Report;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;

final class Tracker_Report_Renderer_TableTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Tracker_Report_Renderer_Table
     */
    private $renderer_table;

    protected function setUp(): void
    {
        $this->renderer_table = new \Tracker_Report_Renderer_Table(1, \Mockery::mock(\Tracker_Report::class), 'Name', 'Description', 1, 10, false);
    }

    public function testShowsExportFeaturesWhenTheUserIsNotAnonymous(): void
    {
        $options_menu = $this->renderer_table->getOptionsMenuItems(UserTestBuilder::aUser()->withId(159)->build());

        self::assertTrue(isset($options_menu['export']));
    }


    public function testDoesNotShowExportFeaturesWhenTheUserIsAnonymous(): void
    {
        $options_menu = $this->renderer_table->getOptionsMenuItems(UserTestBuilder::anAnonymousUser()->build());

        self::assertFalse(isset($options_menu['export']));
    }
}
