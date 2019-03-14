<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Mockery as M;

abstract class SystemEvent_FULLTEXTSEARCH_DOCMANTest extends TuleapTestCase {

    protected $klass;

    /** @var Docman_Item */
    protected $item;

    public function setUp() {
        parent::setUp();
        $this->item    = stub('Docman_Item')->getGroupId()->returns(101);
        $this->version = mock('Docman_Version');
        $this->actions = mock('FullTextSearchDocmanActions');

        $this->item_factory = mock('Docman_ItemFactory');
        stub($this->item_factory)->getItemFromDb(103, '*')->returns($this->item);

        $this->version_factory = M::spy(Docman_VersionFactory::class);
        stub($this->version_factory)->getSpecificVersion($this->item, 2)->returns($this->version);

        $this->link_version_factory = mock('Docman_LinkVersionFactory');
    }

    public function aSystemEventWithParameter($parameters) {
        $id = $type = $owner = $priority = $status = $create_date = $process_date = $end_date = $log = null;
        $event = new $this->klass($id, $type, $owner, $parameters, $priority, $status, $create_date, $process_date, $end_date, $log);
        $event->injectDependencies($this->actions, $this->item_factory, $this->version_factory, $this->link_version_factory);
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
        $event = $this->aSystemEventWithParameter('101::item_that_does_not_exist::2');
        $this->assertFalse($event->process());
        $this->assertEqual($event->getLog(), 'Item not found');
        $this->assertEqual($event->getStatus(), SystemEvent::STATUS_ERROR);
    }
}
?>
