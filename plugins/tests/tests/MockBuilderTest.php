<?php

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

    public function itEnsuresThatMethodIsCalledOnceWithoutArguments() {
        $mock = mock('Toto');
        stub($mock)->greet()->once();
        $mock->greet();
    }

    public function itEnsuresThatMethodIsCalledOnceWithArguments() {
        $mock = mock('Toto');
        stub($mock)->greet('Rasmus', 'Lerdorf')->once();
        $mock->greet('Rasmus', 'Lerdorf');
        //$mock->greet('Rasmus');
    }

    public function itEnsuresThatMethodIsNeverCalled() {
        $mock = mock('Toto');
        stub($mock)->greet()->never();
        //$mock->greet();
    }

    public function itEnsuresThatMethodIsCalledAtWithArguments() {
        $mock = mock('Toto');
        stub($mock)->greet('Lerdorf')->at(0);
        stub($mock)->greet()->at(2);
        stub($mock)->greet('Rasmus', 'Lerdorf')->at(1);
        $mock->greet('Lerdorf');
        //$mock->greet('Tutu');
        $mock->greet('Rasmus', 'Lerdorf');
        $mock->greet();
    }

    public function itEnsuresThatMethodIsCalledNTimes() {
        $mock = mock('Toto');
        stub($mock)->greet()->count(3);
        $mock->greet('Lerdorf');
        //$mock->greet('Tutu');
        $mock->greet('Rasmus', 'Lerdorf');
        $mock->greet();
    }

    public function itCanThrowExceptions() {
        $mock = stub('Toto')->greet()->throws(new SomeException());
        $this->expectException('SomeException');
        $mock->greet();
    }

    public function itCanThrowExceptionAtASpecificTime() {
        $mock = stub('Toto')->greet()->throwsAt(2, new SomeException());
        $mock = stub('Toto')->sayGoodBye()->throwsAt(2, new AnotherException());
        $this->expectException('AnotherException');
        $mock->greet();
        $mock->greet();
        $mock->sayGoodBye();
        $mock->sayGoodBye();
        $mock->sayGoodBye();
    }

    public function itCanThrowExceptionsDependingOnArguments() {
        $mock = stub('Toto')->greet('john')->throws(new SomeException());
        $mock->greet('dave');
        $this->expectException('SomeException');
        $mock->greet('john');
    }

    public function itDoesNotStoreArgumentsBetweenThrowConfigurations() {
        $mock = stub('Toto')->greet('john')->throws(new SomeException());
        stub($mock)->sayGoodbye()->throws(new SomeException());
        $this->expectException('SomeException');
        $mock->sayGoodbye('dave');
    }
}

class Toto {

    function greet() {
        // this function is mocked out in the tests
    }

    function sayGoodBye() {

    }

}
class SomeClass {

    function someMethod() {
        // mocked
    }
}

class SomeException extends Exception {

}

class AnotherException extends Exception {

}

require_once 'SomeClassWithNamespace.php';
class MockBuilder_WithNamespacesTest extends TuleapTestCase {

    public function itCanBuildTheMockIfThereIsANamespace() {
        $classname_to_mock = 'Tuleap\Test\SomeClassWithNamespace';

        $mock = mock($classname_to_mock);
        stub($mock)->someMethod()->returns("a precise result");

        $this->assertEqual(get_class($mock), 'MockTuleap_Test_SomeClassWithNamespace');
        $this->assertIsA($mock, $classname_to_mock);
        $this->assertEqual("a precise result", $mock->someMethod());
    }
}
