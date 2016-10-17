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

class SystemEventProcessor_RootTest extends TuleapTestCase {
    private $system_event_manager;
    private $system_event_dao;
    private $processor;
    private $sys_http_user = 'www-data';
    private $logger;

    public function setUp()
    {
        parent::setUp();
        $this->system_event_manager = mock('SystemEventManager');
        $this->system_event_dao     = mock('SystemEventDao');
        $this->logger               = mock('Logger');
        $this->site_cache           = mock('SiteCache');

        ;
        $this->processor = partial_mock(
            'SystemEventProcessor_Root',
            array('launchAs'),
            array(
                new SystemEventProcessRootDefaultQueue(),
                $this->system_event_manager,
                $this->system_event_dao,
                $this->logger,
                mock('BackendAliases'),
                mock('BackendCVS'),
                mock('BackendSVN'),
                mock('BackendSystem'),
                $this->site_cache,
                mock('Tuleap\Svn\ApacheConfGenerator')
            )
        );
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', '/usr/share/codendi');
        ForgeConfig::set('sys_http_user', $this->sys_http_user);

    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itFetchesEventsForRoot() {
        $category = SystemEvent::DEFAULT_QUEUE;

        $types = array('some_type');
        stub($this->system_event_manager)->getTypesForQueue()->returns($types);

        expect($this->system_event_dao)->checkOutNextEvent('root', $types)->once();
        stub($this->system_event_dao)->checkOutNextEvent()->returns(false);
        $this->processor->execute($category);
    }

    public function itCatchExceptionsInSystemEvents() {
        $system_event = partial_mock('SysteEvent_For_Testing_Purpose', array('process', 'notify', 'verbalizeParameters'));

        $types = array('some_type');
        stub($this->system_event_manager)->getTypesForQueue()->returns($types);
        
        $this->system_event_dao->setReturnValueAt(0, 'checkOutNextEvent', TestHelper::arrayToDar(array('whatever')));
        stub($this->system_event_manager)->getInstanceFromRow()->returns($system_event);

        $system_event->throwOn('process', new RuntimeException('Something wrong happened'));

        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);

        $this->assertEqual($system_event->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertEqual($system_event->getLog(), 'Something wrong happened');
    }

    public function itProcessApplicationOwnerEvents() {
        $command   = '/usr/share/codendi/src/utils/php-launcher.sh /usr/share/codendi/src/utils/process_system_events.php '.SystemEvent::OWNER_APP;
        expect($this->processor)->launchAs($this->sys_http_user, $command)->once();
        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);
    }

    public function itCatchesExceptionsThrownInPostActions() {
        stub($this->processor)->launchAs()->throws(new Exception('Something weird happend'));

        expect($this->logger)->error()->once();

        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);
    }

    public function itRestoreOwnerShipOnGeneratedCacheFiles() {
        expect($this->site_cache)->restoreOwnership()->once();

        $this->processor->execute(SystemEvent::DEFAULT_QUEUE);
    }
}
