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

    public function itWorksWithoutArguments() {
        $mockToto = new MockToto();
        when($mockToto, 'greet')->returns("Hello");
        $this->assertEqual($mockToto->greet(), "Hello");
    }
    
    public function itWorksWithOneArgument() {
        $mockToto = new MockToto();
        when($mockToto, 'greet')->with('Rasmus Lerdorf')->returns("Hello, Rasmus Lerdorf");
        $this->assertEqual($mockToto->greet('Rasmus Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($mockToto->greet('Linus Thorvalds'), "Hello, Rasmus Lerdorf");
    }


}
?>
