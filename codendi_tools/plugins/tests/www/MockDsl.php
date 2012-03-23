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
        if (empty($this->arg)) {
            $this->mock->setReturnValue($this->method, $value);
        } else {
            $this->mock->setReturnValue($this->method, $value, array($this->arg));
        }
    }
    
    function with($arg) {
        $this->arg = $arg;
        return $this;
    }
}
?>
