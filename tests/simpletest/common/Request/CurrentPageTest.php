<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Request;

use TuleapTestCase;

class CurrentPageTest extends TuleapTestCase
{
    /**
     * @var CurrentPage
     */
    private $current_page;
    private $old_request_uri;

    public function setUp()
    {
        parent::setUp();
        $this->old_request_uri = $_SERVER['REQUEST_URI'];

        $this->current_page = new CurrentPage();
    }

    public function tearDown()
    {
        $_SERVER['REQUEST_URI'] = $this->old_request_uri;
        parent::tearDown();
    }

    public function itIsInProjectDashboard()
    {
        $_SERVER['REQUEST_URI'] = '/projects/gpig/';

        $this->assertTrue($this->current_page->isDashboard());
    }

    public function itIsInASpecifiedProjectDashboard()
    {
        $_SERVER['REQUEST_URI'] = '/projects/gpig/?dashboard=666';

        $this->assertTrue($this->current_page->isDashboard());
    }

    public function itIsInUserDashboard()
    {
        $_SERVER['REQUEST_URI'] = '/my/';

        $this->assertTrue($this->current_page->isDashboard());
    }

    public function itIsInASpecifiedUserDashboard()
    {
        $_SERVER['REQUEST_URI'] = '/my/?dashboard=666';

        $this->assertTrue($this->current_page->isDashboard());
    }

    public function itIsNotInDashboardIfUserIsManagingBookmarks()
    {
        $_SERVER['REQUEST_URI'] = '/my/bookmark';

        $this->assertFalse($this->current_page->isDashboard());
    }

    public function itIsNotInDashboardIfUserIsOnAnotherPage()
    {
        $_SERVER['REQUEST_URI'] = '/whatever';

        $this->assertFalse($this->current_page->isDashboard());
    }
}
