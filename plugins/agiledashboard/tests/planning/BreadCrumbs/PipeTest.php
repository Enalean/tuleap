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

Mock::generate("BreadCrumb_BreadCrumbGenerator");
class BreadCrumb_PipeTest extends TuleapTestCase {
    
    public function itMeldsAppendsTheResultOfTheFirstToTheSecond() {
        $bc1 = new MockBreadCrumb_BreadCrumbGenerator();
        $bc1->setReturnValue('getCrumbs', array('lvl1' => "Toto", 'lvl2' => "Tata"));
        $bc2 = new MockBreadCrumb_BreadCrumbGenerator();
        $bc2->setReturnValue('getCrumbs', array('lvl3' => "Tutu"));
        $breadCrumb_Pipe = new BreadCrumb_Pipe($bc1, $bc2);
        $this->assertEqual(array('lvl1' => "Toto", 'lvl2' => "Tata", 'lvl3' => "Tutu"), $breadCrumb_Pipe->getCrumbs());
    }
}
?>
