<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Request;

final class CurrentPageTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var CurrentPage
     */
    private $current_page;

    protected function setUp(): void
    {
        $this->current_page = new CurrentPage();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    public function testItIsInProjectDashboard(): void
    {
        $_SERVER['REQUEST_URI'] = '/projects/gpig/';

        self::assertTrue($this->current_page->isDashboard());
    }

    public function testItIsInASpecifiedProjectDashboard(): void
    {
        $_SERVER['REQUEST_URI'] = '/projects/gpig/?dashboard=666';

        self::assertTrue($this->current_page->isDashboard());
    }

    public function testItIsInUserDashboard(): void
    {
        $_SERVER['REQUEST_URI'] = '/my/';

        self::assertTrue($this->current_page->isDashboard());
    }

    public function testItIsInASpecifiedUserDashboard(): void
    {
        $_SERVER['REQUEST_URI'] = '/my/?dashboard=666';

        self::assertTrue($this->current_page->isDashboard());
    }

    public function testItIsNotInDashboardIfUserIsManagingBookmarks(): void
    {
        $_SERVER['REQUEST_URI'] = '/my/bookmark';

        self::assertFalse($this->current_page->isDashboard());
    }

    public function testItIsNotInDashboardIfUserIsOnAnotherPage(): void
    {
        $_SERVER['REQUEST_URI'] = '/whatever';

        self::assertFalse($this->current_page->isDashboard());
    }
}
