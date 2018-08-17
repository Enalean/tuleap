<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

require_once('common/frs/FRSPackage.class.php');

class FRSPackageTest extends TuleapTestCase {

    function testIsActive() {
        global $GLOBALS;
        
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;
        
        $p = new FRSPackage();
        $p->setStatusId($active_value);
        $this->assertTrue($p->isActive());
        
        $p->setStatusId($hidden_value);
        $this->assertFalse($p->isActive());
        
        $p->setStatusId($deleted_value);
        $this->assertFalse($p->isActive());
    }
    
    function testIsDeleted() {
        global $GLOBALS;
        
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;
        
        $p = new FRSPackage();
        $p->setStatusId($deleted_value);
        $this->assertTrue($p->isDeleted());
        
        $p->setStatusId($hidden_value);
        $this->assertFalse($p->isDeleted());
        
        $p->setStatusId($active_value);
        $this->assertFalse($p->isDeleted());
    }
    
    function testIsHidden() {
        global $GLOBALS;
        
        $active_value = 1;
        $deleted_value = 2;
        $hidden_value = 3;
        
        $p = new FRSPackage();
        $p->setStatusId($hidden_value);
        $this->assertTrue($p->isHidden());
        
        $p->setStatusId($active_value);
        $this->assertFalse($p->isHidden());
        
        $p->setStatusId($deleted_value);
        $this->assertFalse($p->isHidden());
    }
    
    

}
?>
