<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class CrossTrackerWidgetDaoTest extends TestIntegrationTestCase
{
    private CrossTrackerWidgetDao $widget_dao;

    protected function setUp(): void
    {
        $this->widget_dao = new CrossTrackerWidgetDao();
    }

    public function testCreateThenDelete(): void
    {
        self::assertFalse($this->widget_dao->searchWidgetExistence(1));
        $widget_id = $this->widget_dao->createWidget();
        self::assertTrue($this->widget_dao->searchWidgetExistence($widget_id));
        $this->widget_dao->deleteWidget($widget_id);
        self::assertFalse($this->widget_dao->searchWidgetExistence(1));
    }
}
