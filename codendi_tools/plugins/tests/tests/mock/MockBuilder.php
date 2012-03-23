<?php

function givenThat($mock, $method) {
    return new OngoingStub($mock, $method);
}

class OngoingStub {

    public function __construct($mock, $method) {
        $this->mock = $mock;
        $this->method = $method;
    }
    
    public function returns($value) {
        if (empty($this->args)) {
            $this->mock->setReturnValue($this->method, $value);
        } else {
            $this->mock->setReturnValue($this->method, $value, $this->args);
        }
    }
    
    function with() {
        $this->args = func_get_args();
        return $this;
    }
}

/**
 * @param a class name or a simpletest mock
 * @return \OngoingIntelligentStub 
 */
function given($mock) {
    if (is_object($mock)) {
        return new OngoingIntelligentStub($mock);
    } else {
        Mock::generate($mock);
        $classname = "Mock$mock";
        return new OngoingIntelligentStub(new $classname());
    }
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
     * @return the conficured mock 
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
