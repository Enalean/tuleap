<?php

require_once 'MockDsl.php';

class Toto {

    function greet() {
        // this function is mocked out in the tests
    }

}
Mock::generate('Toto');
abstract class MockBuilderBaseTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->mockToto = new MockToto();
    }
    public function itWorksWithoutArguments() {
        $this->mockWithoutArguments();
        
        $this->assertEqual($this->mockToto->greet(), "Hello");
    }
    
    public function itIsPossibleToSpecifyAnArgument() {
        $this->mockWithOneArgument();
                
        $this->assertEqual($this->mockToto->greet('Rasmus Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($this->mockToto->greet('Linus Thorvalds'), "Hello, Rasmus Lerdorf");
    }
    
    public function itIsPossibleToSpecifySeveralArguments() {
        $this->mockWith2Arguments();
        
        $this->assertEqual($this->mockToto->greet('Rasmus', 'Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($this->mockToto->greet('Linus', 'Lerdorf'), "Hello, Rasmus Lerdorf");
        $this->assertNotEqual($this->mockToto->greet('Rasmus', 'Torvalds'), "Hello, Rasmus Lerdorf");
    }
    
    public abstract function mockWithoutArguments();

}
class MockBuilderSimpleTest extends MockBuilderBaseTest {

    public function mockWith2Arguments() {
        givenThat($this->mockToto, 'greet')
            ->with('Rasmus', 'Lerdorf')
            ->returns("Hello, Rasmus Lerdorf");
    }

    public function mockWithOneArgument() {
        givenThat($this->mockToto, 'greet')
            ->with('Rasmus Lerdorf')
            ->returns("Hello, Rasmus Lerdorf");

    }
    public function mockWithoutArguments() {
        givenThat($this->mockToto, 'greet')
                ->returns("Hello");
    }
}

class MockBuilderIntelligentsTest extends MockBuilderBaseTest {
    public function mockWithoutArguments() {
        given($this->mockToto)
            ->greet()
            ->returns("Hello");
    }
    public function mockWithOneArgument() {
        given($this->mockToto)
            ->greet('Rasmus Lerdorf')
            ->returns("Hello, Rasmus Lerdorf");
    }
    public function mockWith2Arguments() {
        given($this->mockToto)
            ->greet('Rasmus', 'Lerdorf')
            ->returns("Hello, Rasmus Lerdorf");
    }
}
?>
