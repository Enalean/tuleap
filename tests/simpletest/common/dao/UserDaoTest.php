<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

class UserDaoTest extends TuleapTestCase
{

    function testReplaceStringInList()
    {
        $da  = mock(\Tuleap\DB\Compat\Legacy2018\LegacyDataAccessInterface::class);
        $dao = new UserDao($da);

        $this->assertEqual($dao->replaceStringInList('foo', 'foo', 'tutu'), 'tutu');
        $this->assertEqual($dao->replaceStringInList('   foo', 'foo', 'tutu'), '   tutu');
        $this->assertEqual($dao->replaceStringInList('foo   ', 'foo', 'tutu'), 'tutu   ');

        $this->assertEqual($dao->replaceStringInList('foo,bar', 'foo', 'tutu'), 'tutu,bar');
        $this->assertEqual($dao->replaceStringInList('foo, bar', 'foo', 'tutu'), 'tutu, bar');
        $this->assertEqual($dao->replaceStringInList('foo ,bar', 'foo', 'tutu'), 'tutu ,bar');

        $this->assertEqual($dao->replaceStringInList('bar,foo,toto', 'foo', 'tutu'), 'bar,tutu,toto');
        $this->assertEqual($dao->replaceStringInList('bar  ,  foo  ,  toto', 'foo', 'tutu'), 'bar  ,  tutu  ,  toto');

        $this->assertEqual($dao->replaceStringInList('bar,wwwfoo,toto', 'foo', 'tutu'), 'bar,wwwfoo,toto');
        $this->assertEqual($dao->replaceStringInList('bar,  wwwfoo,toto ', 'foo', 'tutu'), 'bar,  wwwfoo,toto ');

        $this->assertEqual($dao->replaceStringInList('bar,foowww,foo', 'foo', 'tutu'), 'bar,foowww,tutu');
        $this->assertEqual($dao->replaceStringInList('bar, foowww, foo', 'foo', 'tutu'), 'bar, foowww, tutu');

        $this->assertEqual($dao->replaceStringInList('foo,foo', 'foo', 'tutu'), 'tutu,tutu');
        $this->assertEqual($dao->replaceStringInList('foo,bar,foo', 'foo', 'tutu'), 'tutu,bar,tutu');
    }
}
