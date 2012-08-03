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
require_once dirname(__FILE__).'/../../../include/BreadCrumbs/BreadCrumbGenerator.class.php';
require_once dirname(__FILE__).'/../../../include/BreadCrumbs/Merger.class.php';

class BreadCrumb_PipeTest extends TuleapTestCase {
    private $bc1;
    private $bc2;
    private $bc3;

    public function setUp() {
        parent::setUp();
        $this->bc1 = stub('BreadCrumb_BreadCrumbGenerator')->getCrumbs()->returns(array('lvl1' => "Toto", 'lvl2' => "Tata"));
        $this->bc2 = stub('BreadCrumb_BreadCrumbGenerator')->getCrumbs()->returns(array('lvl3' => "Tutu"));
        $this->bc3 = stub('BreadCrumb_BreadCrumbGenerator')->getCrumbs()->returns(array('lvl4' => "Tralala"));
    }
    
    public function itAppendsTheResultOfTheFirstToTheSecond() {
        $breadcrumb_merger = new BreadCrumb_Merger($this->bc1, $this->bc2);
        $this->assertEqual(array('lvl1' => "Toto", 'lvl2' => "Tata", 'lvl3' => "Tutu"), $breadcrumb_merger->getCrumbs());
    }

    public function itAppendsAllTheBreadCrumbsInTheGivenOrder() {
        $breadcrumb_merger = new BreadCrumb_Merger($this->bc1, $this->bc2, $this->bc3);
        $this->assertEqual(array('lvl1' => "Toto", 'lvl2' => "Tata", 'lvl3' => "Tutu", "lvl4" => "Tralala"), $breadcrumb_merger->getCrumbs());
    }

    public function itAppendsBreadCrumbsWithPushMethod() {
        $breadcrumb_merger = new BreadCrumb_Merger();
        $breadcrumb_merger->push($this->bc1);
        $breadcrumb_merger->push($this->bc2);
        $this->assertEqual(array('lvl1' => "Toto", 'lvl2' => "Tata", 'lvl3' => "Tutu"), $breadcrumb_merger->getCrumbs());
    }
}
?>
