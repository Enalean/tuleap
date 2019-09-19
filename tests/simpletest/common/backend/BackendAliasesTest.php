<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean 2011 - 2018. All rights reserved
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


class BackendAliasesTest extends TuleapTestCase
{

    private $alias_file;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        $GLOBALS['alias_file'] = $this->getTmpDir() . '/aliases.codendi';
        $this->alias_file      = $GLOBALS['alias_file'];

        $listdao = mock('MailingListDao');
        stub($listdao)
            ->searchAllActiveML()
            ->returnsDar(
                array("list_name"=> "list1"),
                array("list_name"=> "list2"),
                array("list_name"=> "list3"),
                array("list_name"=> "list4"),
                array("list_name"=> 'list with an unexpected quote "'),
                array("list_name"=> "list with an unexpected newline\n")
            );

        $this->backend = partial_mock(
            'BackendAliases',
            array(
                'getMailingListDao',
                'system'
            )
        );
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

    public function tearDown()
    {
        unlink($GLOBALS['alias_file']);
        unset($GLOBALS['alias_file']);
        //clear the cache between each tests
        Backend::clearInstances();
        EventManager::clearInstance();
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itReturnsTrueInCaseOfSuccess()
    {
        $this->assertEqual($this->backend->update(), true);
    }

    public function itRunNewaliasesCommand()
    {
        expect($this->backend)->system('/usr/bin/newaliases > /dev/null')->once();
        $this->backend->update();
    }

    public function itGeneratesAnAliasesFile()
    {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertFalse($aliases === false);
    }

    public function itGenerateSiteWideAliases()
    {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertPattern('/codendi-contact/', $aliases, "Codendi-wide aliases not set");
    }

    public function itGeneratesMailingListAliases()
    {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertPattern('/"list1-bounces":/', $aliases, "ML aliases not set");
        $this->assertPattern('/"listwithanunexpectedquote":/', $aliases);
        $this->assertPattern('/"listwithanunexpectednewline":/', $aliases);
    }

    public function itGeneratesUserAliasesGivenByPlugins()
    {
        $this->backend->update();
        $aliases = file_get_contents($this->alias_file);
        $this->assertPattern('/"forge__tracker":/', $aliases, "Alias of plugins not set");
    }
}

class BackendAliasesTest_FakePlugin
{

    public function hook($params)
    {
        $params['aliases'][] = new System_Alias('forge__tracker', 'whatever');
    }
}
