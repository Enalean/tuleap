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

require_once dirname(__FILE__) .'/../include/autoload.php';
require_once 'SystemEvent_FULLTEXTSEARCH_WIKITest.class.php';

class SystemEvent_FULLTEXTSEARCH_WIKI_INDEXTest extends SystemEvent_FULLTEXTSEARCH_WIKITest {

    protected $klass = 'SystemEvent_FULLTEXTSEARCH_WIKI_INDEX';

    public function itAsksToToFullTextSearchActionsIfProjectMappingAlreadyExists() {
        $event = $this->aSystemEventWithParameter('101::wiki_page');

        stub($event)->getWikiPage(101, 'wiki_page')->returns($this->wiki_page);

        expect($this->actions)->checkProjectMappingExists(101)->once();
        $this->assertTrue($event->process());
    }

    public function itAddsDefaultProjectMappingIfProjectMappingDoesNotExist() {
        $event = $this->aSystemEventWithParameter('101::wiki_page');
        stub($event)->getWikiPage(101, 'wiki_page')->returns($this->wiki_page);
        stub($this->actions)->checkProjectMappingExists(101)->returns(false);

        expect($this->actions)->initializeProjetMapping(101)->once();

        $this->assertTrue($event->process());
    }

    public function itDelegatesIndexingToFullTextSearchActions() {
        $event = $this->aSystemEventWithParameter('101::wiki_page');
        stub($event)->getWikiPage(101, 'wiki_page')->returns($this->wiki_page);
        stub($this->actions)->indexNewEmptyWikiPage($this->wiki_page)->once();

        $this->assertTrue($event->process());
        $this->assertEqual($event->getLog(), 'OK');
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_DONE);
    }

}