<?php
/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */
require_once('common/project/ProjectManager.class.php');
Mock::generatePartial('ProjectManager', 'ProjectManagerTestVersion', array('createProjectInstance'));
class ProjectManagerTest extends UnitTestCase {
    function __construct($name = 'ProjectManager test') {
        $this->UnitTestCase($name);
    }
    
    function testGetProject() {
        $p = new ProjectManagerTestVersion($this);
        $p->setReturnReferenceAt(0, 'createProjectInstance', $this);
        $p->setReturnReferenceAt(1, 'createProjectInstance', $p);
        
        $o1 = $p->getProject(1);
        $o2 = $p->getProject(1);
        $o3 = $p->getProject(2);
        $this->assertReference($o1, $o2);
        $this->assertNotEqual($o1, $o3);
    }
    /**/
    function testClear() {
        $p = new ProjectManagerTestVersion($this);
        $p->setReturnReference('createProjectInstance', $this);
        $p->expectArgumentsAt(0, 'createProjectInstance', array(1));
        $p->expectArgumentsAt(1, 'createProjectInstance', array(2));
        $p->expectArgumentsAt(2, 'createProjectInstance', array(1));
        
        $p->getProject(1);
        $p->getProject(1);
        $p->getProject(2);
        $p->getProject(1);
        $p->clear(1);
        $p->getProject(1);
    }
    /**/
}
?>
