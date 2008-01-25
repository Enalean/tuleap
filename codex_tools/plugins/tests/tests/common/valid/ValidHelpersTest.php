<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('common/valid/ValidFactory.class.php');
Mock::generate('Valid');

class ValidHelperTest extends UnitTestCase {

    function UnitTestCase($name = 'ValidFactory test') {
        $this->UnitTestCase($name);
    }

    function testUInt() {
        $v = new Valid_UInt();
        $v->disableFeedback();

        $this->assertTrue($v->validate('0'));
        $this->assertTrue($v->validate('1'));
        $this->assertTrue($v->validate('2147483647'));

        $this->assertFalse($v->validate('-1'));
        // With a value lower than -2^31 it may imply a int overflow that may
        // generate a positive int (in this case: 2^31-1).
        $this->assertFalse($v->validate('-2147483649'));
        $this->assertFalse($v->validate('0.5'));
        $this->assertFalse($v->validate('toto'));
    }

    function testMultidimensionalArray() {
        $v = new Valid_MultidimensionalArray('', array());
        $v->disableFeedback();
        
        $this->assertFalse($v->validate(1), 'An int is not a multi-dimensional array');
        $this->assertFalse($v->validate('string'), 'A string is not a multi-dimensional array');
        $this->assertFalse($v->validate(true), 'A boolean is not a multi-dimensional array');
        
        $this->assertTrue($v->validate(array()), 'An empty array is a multi-dimensional array');
        $this->assertFalse($v->validate(array(1, 2, 3)), 'A simple array is not a multi-dimensional array');
        $this->assertTrue($v->validate(array(array(), array())), 'An empty multi dimensional array is a multi-dimensional array');
    }
    
    function testMultidimensionalArrayCheckAllValidatorsChecked() {
        $v1 = new MockValid();
        $v1->setReturnValue('validate', true, array(1));
        $v1->setReturnValue('validate', true, array(4));
        $v1->expectCallCount('validate', 2);
        $v2 = new MockValid();
        $v2->setReturnValue('validate', true, array(2));
        $v2->setReturnValue('validate', false, array(5));
        $v2->expectCallCount('validate', 2);
        $v3 = new MockValid();
        $v3->setReturnValue('validate', true, array(3));
        $v3->setReturnValue('validate', true, array(6));
        $v3->expectCallCount('validate', 2);
        
        $v = new Valid_MultidimensionalArray('', array($v1, $v2, $v3));
        $v->disableFeedback();
        $this->assertFalse($v->validate(array(array(1,2,3), array(4,5,6))));
        
        $v1->tally();
        $v2->tally();
        $v3->tally();
        
    }
    
    function testMultidimensionalArrayRetPropagation() {
        $v1 = new MockValid();
        $v1->setReturnValue('validate', true);
        $v2 = new MockValid();
        $v2->setReturnValue('validate', false);
        $v3 = new MockValid();
        $v3->setReturnValue('validate', true);
        
        $v = new Valid_MultidimensionalArray('', array($v1, $v2, $v3));
        $v->disableFeedback();
        
        $this->assertFalse($v->validate(array(array(1, 2, 3))));
    }
}

?>