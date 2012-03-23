<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'MockDsl.php';

class Toto {

    function __construct() {
        
    }
    function greet() {
        
    }

}
Mock::generate('Toto');
class MockBuilderTest extends TuleapTestCase {

    public function testSomething() {
        $mockToto = new MockToto();
        when($mockToto, 'greet')->returns("Hello");
        $this->assertEqual($mockToto->greet(), "Hello");
    }

}
?>
