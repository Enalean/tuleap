<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'builders/aSystemEvent.php';

class SystemEventTest extends TuleapTestCase
{

    public function itRetrievesAParameterByItsIndex()
    {
        $event = aSystemEvent()->withParameters('B::A')->build();
        $this->assertEqual('A', $event->getParameter(1));
        $this->assertEqual('B', $event->getRequiredParameter(0));
    }

    public function itReturnsNullIfIndexNotFound()
    {
        $event = aSystemEvent()->withParameters('')->build();
        $this->assertNull($event->getParameter(0));
    }

    public function itRaisesAnExceptionWhenParameterIsRequiredAndNotFound()
    {
        $event = aSystemEvent()->withParameters('')->build();
        $this->expectException('SystemEventMissingParameterException');
        $event->getRequiredParameter(0);
    }

    public function itProperlyEncodesAndDecodesData()
    {
        $data = array('coin' => 'String that contains :: (the param separator)');
        $this->assertEqual($data, SystemEvent::decode(SystemEvent::encode($data)));
    }
}
