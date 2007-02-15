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
    
    var $id;
	var $name;
    var $description;
    var $url;
    /**
    * Constructor
    */
    function Server($data = array()) {
        $this->id          = isset($data['id'])          ? $data['id']          : null;
        $this->name        = isset($data['name'])        ? $data['name']        : '';
        $this->description = isset($data['description']) ? $data['description'] : '';
        $this->url         = isset($data['url'])         ? $data['url']         : '';
    }
    function getId() {
        return $this->id;
    }
    function getName() {
        return $this->name;
    }
    function getDescription() {
        return $this->description;
    }
    function getUrl() {
        return $this->url;
    }
}
?>
