<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once dirname(__FILE__) .'/../include/autoload.php';

class SystemEvent_FULLTEXTSEARCH_TRACKER_REINDEX_PROJECTTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->actions = mock('FullTextSearchTrackerActions');
    }

    public function aSystemEventWithParameter($parameters) {
        $id = $type = $owner = $priority = $status = $create_date = $process_date = $end_date = $log = null;
        $event = new SystemEvent_FULLTEXTSEARCH_TRACKER_REINDEX_PROJECT(
            $id,
            $type,
            $owner,
            $parameters,
            $priority,
            $status,
            $create_date,
            $process_date,
            $end_date,
            $log
        );
        $event->setFullTextSearchTrackerActions($this->actions);
        return $event;
    }

    public function itRequiresGroupIdInParameters() {
        $event = $this->aSystemEventWithParameter('');
        $this->assertFalse($event->process());
        $this->assertNotNull($event->getLog());
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_ERROR);
    }
}