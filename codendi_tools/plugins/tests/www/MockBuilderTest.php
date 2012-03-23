<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once 'MockDsl.php';

class Toto {

    function greet() {
        // this function is mocked out in the tests
    }

}
Mock::generate('Toto');
class MockBuilderTest extends TuleapTestCase {

    public function itWorksWithoutArguments() {
        $mockToto = new MockToto();
        
        givenThat($mockToto, 'greet')
                ->returns("Hello");
        
        $this->assertEqual($mockToto->greet(), "Hello");
    }
    
    public function itIsPossibleToSpecifyAnArgument() {
        $mockToto = new MockToto();
        
        givenThat($mockToto, 'greet')
                ->with('Rasmus Lerdorf')
                ->returns("Hello, Rasmus Lerdorf");
        
        $this->assertEqual($mockToto->greet('Rasmus Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($mockToto->greet('Linus Thorvalds'), "Hello, Rasmus Lerdorf");
    }
    
    public function itIsPossibleToSpecifySeveralArguments() {
        $mockToto = new MockToto();
        
        givenThat($mockToto, 'greet')
                ->with('Rasmus', 'Lerdorf')
                ->returns("Hello, Rasmus Lerdorf");
        
        $this->assertEqual($mockToto->greet('Rasmus', 'Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($mockToto->greet('Linus', 'Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($mockToto->greet('Rasmus', 'Torvalds'), "Hello, Rasmus Lerdorf");
    }


}
?>
