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
?>
