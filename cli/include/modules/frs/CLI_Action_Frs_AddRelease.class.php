<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_AddRelease extends CLI_Action {
    function __construct() {
        parent::__construct('addRelease', 'Add a new release in frs manager.');
        $this->addParam(array(
            'name'           => 'package_id',
            'description'    => '--package_id=<package_id>    Id of the package the the release will belong to.',
        ));
        $this->addParam(array(
            'name'           => 'name',
            'description'    => '--name=<release_name>        Name of the release.',
        ));
        $this->addParam(array(
            'name'           => 'notes',
            'description'    => '--notes=<notes>              Notes associated with this release.',
        ));
        $this->addParam(array(
            'name'           => 'changes',
            'description'    => '--changes=<changes>          Change log associated with this release.',
        ));
        $this->addParam(array(
            'name'           => 'status_id',
            'description'    => '--status_id=<status_id>      Status of this release. Optional, default=1 (active).',
        ));
        $this->addParam(array(
            'name'           => 'release_date',
            'description'    => '--release_date=<release_date> The date of the release, in format YYYY-MM-DD.',
        ));
    }
    function validate_package_id(&$package_id) {
        if (!$package_id) {
            exit_error("You must specify the ID of the package with the --package_id parameter");
        }
        return true;
    }
    function validate_name(&$name) {
        if (!$name) {
            exit_error("You must specify the name of the release with the --name parameter");
        }
        return true;
    }
    function validate_notes(&$notes) {
        if (!$notes) {
            $notes = '';
        }
        return true;
    }
    function validate_changes(&$changes) {
        if (!$changes) {
            $changes = '';
        }
        return true;
    }
    function validate_status_id(&$status_id) {
        if (!$status_id) {
            $status_id = 1;
        }
        return true;
    }
    function validate_release_date(&$release_date) {
        if (!$release_date) {
            $release_date = time();
        } else {
            $date_check = $this->check_date($release_date);
            if ($date_check != "") {
                exit_error($release_date . ' ' . $date_check);
            } else {
                $release_date = $this->convert_date($release_date);
            }
        }
        return true;
    }
}
