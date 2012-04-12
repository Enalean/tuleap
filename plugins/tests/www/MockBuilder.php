<?php

/**
 * Returns a DSL like mockgenerator : <br>
 *   stub('someclass')->someMethod($arg1, $arg2, ...)->returns($someResult); <br>
 * that is an alternative to 
 * 
 * Mock::generate('SomeClass');<br>
 * $mock = new MockSomeClass();<br>
 * $mock->setReturnValue('someMethod', $someResult, array($arg1, $arg2, ...);<br>
 * 
 * @param a class name or a simpletest mock
 * @return \OngoingIntelligentStub 
 */
function stub($classname_or_simpletest_mock) {
    if (is_object($classname_or_simpletest_mock)) {
        $mock = $classname_or_simpletest_mock;
    } else {
        $mock = mock($classname_or_simpletest_mock);
    }
    return new OngoingIntelligentStub($mock);
}

/**
 * mock('SomeClass');
 * 
 * is exactly the same as
 * 
 * Mock::generate('SomeClass');<br>
 * $mock = new MockSomeClass();

 * @param type $classname
 * @return a simpletest mock 
 */
function mock($classname) {
    Mock::generate($classname);
    $mockclassname = "Mock$classname";
    return new $mockclassname();
}

class OngoingIntelligentStub {

    function __construct($mock) {
        $this->mock = $mock;
    }

    public function __call($name, $arguments) {
        $this->method = $name;
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return the configured mock 
     */
    public function returns($value) {
        if (empty($this->arguments)) {
            $this->mock->setReturnValue($this->method, $value);
        } else {
            $this->mock->setReturnValue($this->method, $value, $this->arguments);
        }
        return $this->mock;
    }
    

}
?>
