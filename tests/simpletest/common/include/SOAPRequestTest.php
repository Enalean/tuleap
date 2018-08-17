<?php

require_once('common/include/SOAPRequest.class.php');

class SOAPRequestTest extends TuleapTestCase {

    function testInit() {
       $request = new SOAPRequest(array(
           'param_1' => 'value_1',
           'param_2' => 'value_2')
       );
       $this->assertEqual($request->get('param_1'), 'value_1');
       $this->assertEqual($request->get('param_2'), 'value_2');
       $this->assertFalse($request->get('does_not_exist')); 
    }/**/
}
?>
