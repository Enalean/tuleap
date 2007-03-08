<?php

/**
* ArtifactGlobalNotification
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
class ArtifactGlobalNotification {
    
    var $data;
	
    /**
    * Constructor
    */
    function ArtifactGlobalNotification($data) {
        $this->data = $data;
    }
    function getId() {
        return $this->data['id'];
    }
    function getTrackerId() {
        return $this->data['tracker_id'];
    }
    function getAddresses() {
        return $this->data['addresses'];
    }
    function isAllUpdates() {
        return $this->data['all_updates'];
    }
    function isCheckPermissions() {
        return $this->data['check_permissions'];
    }
}
?>
