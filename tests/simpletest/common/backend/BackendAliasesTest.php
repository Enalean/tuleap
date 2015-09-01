<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2011, 2012, 2013, 2014. All rights reserved
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


class BackendAliasesTest extends TuleapTestCase {

    private $alias_file;

    public function setUp() {
        $GLOBALS['alias_file'] = dirname(__FILE__) . '/_fixtures/etc/aliases.codendi';
        $this->alias_file      = $GLOBALS['alias_file'];

        $udao = mock('UserDao');
        stub($udao)
            ->searchByStatus()
            ->returnsDar(
                array(
                    "user_name"=> "user1",
                    "email"    => "user1@domain1.com"
                ),
                array(
                    "user_name"=> "user2",
                    "email"    => "user1@domain2.com"
                ),
                array(
                    "user_name"=> "user3",
                    "email"    => "user1@domain3.com"
                )
            );

        $listdao = mock('MailingListDao');
        stub($listdao)
            ->searchAllActiveML()
            ->returnsDar(
                array("list_name"=> "list1"),
                array("list_name"=> "list2"),
                array("list_name"=> "list3"),
                array("list_name"=> "list4")
            );

        $this->backend = partial_mock(
            'BackendAliases',
            array(
                'getUserDao',
                'getMailingListDao',
                'system'
            )
        );
        stub($this->backend)->getUserDao()->returns($udao);
        stub($this->backend)->getMailingListDao()->returns($listdao);
        stub($this->backend)->system()->returns(true);

        $plugin = new BackendAliasesTest_FakePlugin();
        EventManager::instance()->addListener(
            Event::BACKEND_ALIAS_GET_ALIASES,
            $plugin,
            'hook',
            false
        );
    }

    public function tearDown() {
        unlink($GLOBALS['alias_file']);
        unset($GLOBALS['alias_file']);
        //clear the cache between each tests
        Backend::clearInstances();
        EventManager::clearInstance();
    }

    public function itReturnsTrueInCaseOfSuccess() {
        $this->assertEqual($this->backend->update(), true);
    }

    public function itRunNewaliasesCommand() {
        expect($this->backend)->system('/usr/bin/newaliases > /dev/null')->once();
        $this->backend->update();
    }

    public function itGeneratesAnAliasesFile() {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertFalse($aliases === false);
    }

    public function itGenerateSiteWideAliases() {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertPattern("/codendi-contact/", $aliases, "Codendi-wide aliases not set");
    }

    public function itGeneratesMailingListAliases() {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertPattern("/list1-bounces:/", $aliases, "ML aliases not set");
    }

    public function itGeneratesUserAliases() {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertPattern("/user3:/", $aliases, "User aliases not set");
    }

    public function itGeneratesUserAliasesGivenByPlugins() {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertPattern("/forge__tracker:/", $aliases, "Alias of plugins not set");
    }
}

class BackendAliasesTest_FakePlugin {

    public function hook($params) {
        $params['aliases'][] = new System_Alias('forge__tracker', 'whatever');
    }
}
