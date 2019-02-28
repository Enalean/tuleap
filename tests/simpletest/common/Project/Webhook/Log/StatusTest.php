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

namespace Tuleap\Project\Webhook\Log;

class StatusTest extends \TuleapTestCase
{
    public function itUsesGivenInformation()
    {
        $status = new Status('200 OK', 1489595500);

        $this->assertEqual('200 OK', $status->getStatus());
    }

    public function itDeterminesTheStatusIsSuccessful()
    {
        $status = new Status('200 OK', 1489595500);

        $this->assertFalse($status->isInError());
    }

    public function itIsInErrorIfTheStatusIsEmpty()
    {
        $status = new Status('', 1489595500);

        $this->assertTrue($status->isInError());
    }

    public function itIsInErrorIfWeGotAnHTTPErrorCode()
    {
        $status = new Status('500 Internal Server Error', 1489595500);

        $this->assertTrue($status->isInError());
    }

    public function itIsInErrorWhenCurlGivesAnError()
    {
        $status = new Status('Operation timed out after 5000 milliseconds with 0 bytes received', 1489595500);

        $this->assertTrue($status->isInError());
    }
}
