<?php
/**
* Server
* 
* A server is a physical box that serves CodeX.
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class Server {
    
    var $data;
	
    /**
    * Constructor
    */
    function Server($data) {
        $this->data = $data;
    }
    function getId() {
        return $this->data['id'];
    }
    function getName() {
        return $this->data['name'];
    }
}
?>
