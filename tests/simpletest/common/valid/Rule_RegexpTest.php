<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class Rule_RegexpTest extends TuleapTestCase
{
    function __construct($name = 'Rule_Int test')
    {
        parent::__construct($name);
    }

    function testPattern()
    {
        $r = new Rule_Regexp('/[a-z]+/');

        $input = '123';
        $this->assertFalse($r->isValid($input));

        $input = 'abc';
        $this->assertTrue($r->isValid($input));
    }
}
