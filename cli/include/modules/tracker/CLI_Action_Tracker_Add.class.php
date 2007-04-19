<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* $Id$
*/

require_once('CLI_Action_Tracker_MajFields.class.php');

class CLI_Action_Tracker_Add extends CLI_Action_Tracker_MajFields {
    function CLI_Action_Tracker_Add() {
        $this->CLI_Action_Tracker_MajFields('add', 'Add a new artifact in a tracker.');
        $this->soapCommand = 'addArtifactWithFieldNames';
    }
    function getGroupArtifactIdDescription() {
        return 'The ID of the tracker the returned artifacts belong to.';
    }
}

?>
