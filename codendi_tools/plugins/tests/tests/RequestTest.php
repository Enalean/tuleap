<?php

require_once('../include/testsPluginRequest.php');

class testsPluginRequestTest extends TuleapTestCase {
    
    protected $requestObject;
    
    public function setUp() {
        $this->requestObject = new testsPluginRequest();
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
    public function itCanParseTestsToRunWithArrayArgument() {
        $expected = array('test1'=>true);
        $this->requestObject->parse(array('tests_to_run'=>$expected));
        $this->assertEqual($this->requestObject->getTestsToRun(), $expected);
        
    }
    
}
?>