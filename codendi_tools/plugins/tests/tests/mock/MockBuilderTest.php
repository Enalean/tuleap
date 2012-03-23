<?php

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
        

//        $mockToto = when('Toto')
//                ->recieves('greet')
//                ->with('Rasmus Lerdorf')
//                ->returns("Hello, Rasmus Lerdorf");
        
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
    
    public function itWorksWithoutArgumentsV2() {
        $mockToto = new MockToto();
        
        givenThat($mockToto, 'greet')
                ->returns("Hello");
        
        $this->assertEqual($mockToto->greet(), "Hello");
    }
    
    public function testVersion2() {
        $mockToto = new MockToto();

        given($mockToto)
            ->greet('Rasmus Lerdorf')
            ->returns("Hello, Rasmus Lerdorf");
        
        $this->assertEqual($mockToto->greet('Rasmus Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($mockToto->greet('Linus Thorvalds'), "Hello, Rasmus Lerdorf");

    }
    public function itIsPossibleToSpecifySeveralArgumentsV2() {
        $mockToto = new MockToto();
        
        given($mockToto)
            ->greet('Rasmus', 'Lerdorf')
            ->returns("Hello, Rasmus Lerdorf");
        
        $this->assertEqual($mockToto->greet('Rasmus', 'Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($mockToto->greet('Linus', 'Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($mockToto->greet('Rasmus', 'Torvalds'), "Hello, Rasmus Lerdorf");
    }
    

}
?>
