<?php

function when($mock, $method) {
    return new OngoingStub($mock, $method);

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
