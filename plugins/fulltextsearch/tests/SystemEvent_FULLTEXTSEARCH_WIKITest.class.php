<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

abstract class SystemEvent_FULLTEXTSEARCH_WIKITest extends TuleapTestCase {

    protected $klass;

    /** @var WikiPage */
    protected $wiki_page;

    public function setUp() {
        parent::setUp();
        $this->wiki_page = mock(\Tuleap\PHPWiki\WikiPage::class);
        $this->group_id  = 101;
        $this->actions   = mock('FullTextSearchWikiActions');
    }

    public function aSystemEventWithParameter($parameters) {
        $id = $type = $owner = $priority = $status = $create_date = $process_date = $end_date = $log = null;
        $event = partial_mock(
            $this->klass,
            array('getWikiPage'),
            array($id, $type, $owner, $parameters, $priority, $status, $create_date, $process_date, $end_date, $log)
        );
        $event->injectDependencies($this->actions);

        return $event;
    }

    public function itRequiresGroupIdInParameters() {
        $event = $this->aSystemEventWithParameter('');

        $this->assertFalse($event->process());
        $this->assertNotNull($event->getLog());
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_ERROR);
    }

    public function itRequiresItemIdInParameters() {
        $event = $this->aSystemEventWithParameter('101');

        $this->assertFalse($event->process());
        $this->assertNotNull($event->getLog());
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_ERROR);
    }

    public function itFailsIfItemIsNotFound() {
        $event = $this->aSystemEventWithParameter('101::wiki_page_that_not_exists');

        stub($event)->getWikiPage(101, 'wiki_page_that_not_exists')->returns(null);

        $this->assertFalse($event->process());
        $this->assertEqual($event->getLog(), 'Wiki page not found');
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_ERROR);
    }
}