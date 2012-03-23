<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Toto {

    function __construct() {
        
    }
    function greet() {
        
    }

}
Mock::generate('Toto');
class MockBuilderTest extends TuleapTestCase {

    function __construct() {
        
    }
    
    public function testSomething() {
        $mockToto = new MockToto();
        $this->when($mockToto, 'greet')->returns("Hello");
        $this->assertEqual($mockToto->greet(), "Hello");
    }

    public function when($mock, $method) {
        return new OngoingStub($mock, $method);
        
    }
}
class OngoingStub {

    public function __construct($mock, $method) {
        $this->mock = $mock;
        $this->method = $method;
    }
    
    public function returns($value) {
        $this->mock->setReturnValue($this->method, $value);
    }
    

}
?>
