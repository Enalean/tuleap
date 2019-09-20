<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Git;

use TuleapTestCase;

require_once 'bootstrap.php';

class PathJoinUtilTest extends TuleapTestCase
{

    function testEmptyArrayReturnsEmptyPath()
    {
        $this->assertEqual('', PathJoinUtil::unixPathJoin(array()));
    }

    function testTheSlashInFrontOfTheFirstElementIsKept()
    {
        $this->assertEqual('/toto', PathJoinUtil::unixPathJoin(array('/toto')));
        $this->assertEqual('/toto/tata/', PathJoinUtil::unixPathJoin(array('/toto', 'tata/')));
    }

    function testAtTheEndThereIsASlashOnlyIfTheLastElementHasOne()
    {
        $this->assertEqual('toto/', PathJoinUtil::unixPathJoin(array('toto/')));
        $this->assertEqual('toto/tata/', PathJoinUtil::unixPathJoin(array('toto','tata/')));
    }

    function testRemoveSlashesWhenThereAreMoreThanOne()
    {
        $this->assertEqual('/toto', PathJoinUtil::unixPathJoin(array('//toto')));
        $this->assertEqual('toto/tata', PathJoinUtil::unixPathJoin(array('toto/', '/tata')));
        $this->assertEqual('/toto/tata/titi/tutu', PathJoinUtil::unixPathJoin(array('/toto/', '/tata/', '/titi/', '//tutu')));
    }

    function testAllEmptyElementsAreIgnored()
    {
        $this->assertEqual('toto/0', PathJoinUtil::unixPathJoin(array('', null, 'toto', '0')));
    }

    function testUserRepoPath_IsPrefixedByUsername()
    {
        $this->assertEqual('u/nicolas', PathJoinUtil::userRepoPath('nicolas', ''));
        $this->assertEqual('u/nicolas/toto', PathJoinUtil::userRepoPath('nicolas', 'toto'));
    }
    function testUserRepoPath_ComplainsWhenThereAreDoubleDots()
    {
        $this->expectException('MalformedPathException');
        PathJoinUtil::userRepoPath('nicolas', '..');
    }
    function testUserRepoPath_ComplainsWhenUserTriesToByPathItsHomeDirectory()
    {
        $this->expectException('MalformedPathException');
        $this->assertEqual('u/nicolas/root', PathJoinUtil::userRepoPath('nicolas', '/users/../root'));
    }
    // ''     => u/nicolas
    // toto   => u/nicolas/toto
    //removes .. in the path
}
