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
require_once 'common/system_event/SystemEventProcessor.class.php';

class SystemEventProcessorTest extends TuleapTestCase {

    public function itCatchExceptionsInSystemEvents() {
        $system_event = partial_mock('SysteEvent_For_Testing_Purpose', array('process', 'notify', 'verbalizeParameters'));

        $system_event_manager = mock('SystemEventManager');
        $system_event_dao     = mock('SystemEventDao');
        $system_event_dao->setReturnValueAt(0, 'checkOutNextEvent', TestHelper::arrayToDar(array('whatever')));
        stub($system_event_manager)->getInstanceFromRow()->returns($system_event);

        $system_event->throwOn('process', new RuntimeException('Something wrong happened'));

        $processor = new SystemEventProcessor(
            $system_event_manager,
            $system_event_dao,
            mock('BackendAliases'),
            mock('BackendCVS'),
            mock('BackendSVN'),
            mock('BackendSystem')
        );
        $processor->process();

        $this->assertEqual($system_event->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertEqual($system_event->getLog(), 'Something wrong happened');
    }
}

?>
