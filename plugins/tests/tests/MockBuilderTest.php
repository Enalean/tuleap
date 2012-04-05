<?php

require_once dirname(__FILE__) .'/../www/MockBuilder.php';

abstract class MockBuilderBaseTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        Mock::generate('Toto');
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

class MockBuilderIntelligentsTest extends MockBuilderBaseTest {
    public function mockWithoutArguments() {
        stub($this->mockToto)
            ->greet()
            ->returns("Hello");
    }
    public function mockWithOneArgument() {
        stub($this->mockToto)
            ->greet('Rasmus Lerdorf')
            ->returns("Hello, Rasmus Lerdorf");
    }
    public function mockWith2Arguments() {
        stub($this->mockToto)
            ->greet('Rasmus', 'Lerdorf')
            ->returns("Hello, Rasmus Lerdorf");
    }
    
    public function itCanAlsoBuildTheMock() {
       $mockOfSomeClass = stub('SomeClass')
                            ->someMethod()
                            ->returns("a precise result");
       $this->assertEqual("a precise result", $mockOfSomeClass->someMethod());
    }
    
    public function itCanStubMoreThanOneMethod() {
        $mock = stub('Toto')
            ->greet('John Doe')
            ->returns('Hello, John Doe');

        stub($mock)
            ->greet('Rasmus', 'Lerdorf')
            ->returns('Hello, Rasmus Lerdorf');

        $this->assertEqual($mock->greet('John Doe'), 'Hello, John Doe');
        $this->assertEqual($mock->greet('Rasmus', 'Lerdorf'), 'Hello, Rasmus Lerdorf');
    }    
}

class Toto {

    function greet() {
        // this function is mocked out in the tests
    }

}
class SomeClass {

    function someMethod() {
        // mocked
    }
}
?>
