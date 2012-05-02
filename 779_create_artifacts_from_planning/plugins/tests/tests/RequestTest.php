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

require_once dirname(__FILE__).'/../include/TestsPluginRequest.class.php';

class PluginRequestTest extends TuleapTestCase {
    
    protected $requestObject;
    
    public function setUp() {
        parent::setUp();
        $this->requestObject = new TestsPluginRequest();
    }
    
    public function itAsADefaultCoverCodeToFalse() {
        $this->requestObject->parse(array());
        $this->assertFalse($this->requestObject->getCoverCode());
        
    }
    
    public function itCanParseCoverCodeWithArrayArgument() {
        $this->requestObject->parse(array('cover_code'=>true));
        $this->assertTrue($this->requestObject->getCoverCode());
        
    }
    
    public function itParseCoverCodeAsABoolean() {
        $this->requestObject->parse(array('cover_code'=>'true'));
        $this->assertTrue($this->requestObject->getCoverCode());  
        $this->requestObject->parse(array('cover_code'=>null));
        $this->assertFalse($this->requestObject->getCoverCode());
    }
    
    public function itAsADefaultShowPassToFalse() {
        $this->requestObject->parse(array());
        $this->assertFalse($this->requestObject->getShowPass());
        
    }
    
    public function itCanParseShowPassWithArrayArgument() {
        $this->requestObject->parse(array('show_pass'=>true));
        $this->assertTrue($this->requestObject->getShowPass());
        
    }
    
    public function itParseShowPassAsABoolean() {
        $this->requestObject->parse(array('show_pass'=>'true'));
        $this->assertTrue($this->requestObject->getShowPass());
        $this->requestObject->parse(array('show_pass'=>null));
        $this->assertFalse($this->requestObject->getShowPass());        
    }
    
    public function itAsADefaultOrderToNormal() {
        $this->requestObject->parse(array());
        $this->assertEqual($this->requestObject->getOrder(), 'normal');
        
    }
    
    public function itCanParseOrderWithArrayArgumentIfItValueIsRight() {
        
        $this->requestObject->parse(array('order'=>true));
        $this->assertEqual($this->requestObject->getOrder(), 'normal');
        
        $this->requestObject->parse(array('order'=>'random'));
        $this->assertEqual($this->requestObject->getOrder(), 'random');
        
        $this->requestObject->parse(array('order'=>'invert'));
        $this->assertEqual($this->requestObject->getOrder(), 'invert');
        
    }
    
    public function itParseOrderWithoutGettingRidOfCase() {
        
        $this->requestObject->parse(array('order'=>'InVErT'));
        $this->assertEqual($this->requestObject->getOrder(), 'invert');
        
    }
    
    
}
?>