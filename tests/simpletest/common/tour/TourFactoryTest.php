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

class TourFactoryTest extends TuleapTestCase {

    protected $factory;

    protected $fixtures_dir;

    public function setUp() {
        parent::setUp();

        Config::set('sys_custom_incdir', $this->fixtures_dir);

        $this->factory      = new Tuleap_TourFactory();
        $this->fixtures_dir = dirname(__FILE__) .'/_fixtures';
    }
}

class TourFactoryTest_loadToursForPage extends TourFactoryTest {

    /** @var PFUser */
    private $user;

    public function setUp() {
        parent::setUp();
        $GLOBALS['Response'] = partial_mock('Response', array());
        $this->user = mock('PFUser');
    }

    public function itDoesNotAddToursIfCustomTourFolderDoesntExist() {
        $request_uri = '';
        Config::set('sys_custom_incdir', $this->fixtures_dir.'/somewhereElse');
        stub($this->user)->getLocale()->returns('en_US');

        $tours = $this->factory->getCustomToursForPage($this->user, $request_uri);
        $this->assertEqual(count($tours), 0);
    }

    public function itLoadsAllToursInTheCustomToursListForPage() {
        $request_uri = '/plugins/lala';
        stub($this->user)->getLocale()->returns('en_US');

        $tours = $this->factory->getCustomToursForPage($this->user, $request_uri);
        $this->assertEqual(count($tours), 1);

        $this->assertArrayNotEmpty($tours);
    }

    public function itLoadsToursInCorrectLanguage() {
        stub($this->user)->getLocale()->returns('fr_FR');
        $request_uri         = '/plugins/lala';
        $factory             = new Tuleap_TourFactory();

        $tours = $factory->getCustomToursForPage($this->user, $request_uri);
        $this->assertEqual(count($tours), 0);
    }
}