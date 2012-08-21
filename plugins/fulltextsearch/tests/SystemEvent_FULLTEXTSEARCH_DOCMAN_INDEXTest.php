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

require_once dirname(__FILE__) .'/../include/SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX.class.php';
require_once 'SystemEvent_FULLTEXTSEARCH_DOCMANTest.class.php';

class SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEXTest extends SystemEvent_FULLTEXTSEARCH_DOCMANTest {

    protected $klass = 'SystemEvent_FULLTEXTSEARCH_DOCMAN_INDEX';

    public function itRequiresVersionNumberInParameters() {
        $event = $this->aSystemEventWithParameter('101::103');
        $this->assertFalse($event->process());
        $this->assertNotNull($event->getLog());
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_ERROR);
    }

    public function itFailsIfVersionIsNotFound() {
        $event = $this->aSystemEventWithParameter('101::103::version_that_does_not_exist');
        $this->assertFalse($event->process());
        $this->assertEqual($event->getLog(), 'Version not found');
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_ERROR);
    }

    public function itDelegatesIndexingToFullTextSearchActions() {
        $event = $this->aSystemEventWithParameter('101::103::2');
        stub($this->actions)->indexNewDocument($this->item, $this->version)->once();
        $this->assertTrue($event->process());
        $this->assertEqual($event->getLog(), 'OK');
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_DONE);
    }
}
?>
