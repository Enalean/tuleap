<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2007. All rights reserved
*
*
*/

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_AddPackage extends CLI_Action {
    function __construct() {
        parent::__construct('addPackage', 'Add a new package in frs manager.');
        $this->addParam(array(
            'name'           => 'package_name',
            'description'    => '--name=<package_name>      Name of the package.',
            'parameters'     => array('name'),
        ));
        $this->addParam(array(
            'name'           => 'status_id',
            'description'    => '--status_id=<status_id>    Status of this package. Optional, default=1 (active).',
        ));
        $this->addParam(array(
            'name'           => 'rank',
            'description'    => '--rank=<rank>              Rank of the package in the package list. Optional, default=0.',
        ));
        $this->addParam(array(
            'name'           => 'approve_license',
            'description'    => '--approve_license=<approve_license> approve_license. Optionnal, default=1.',
        ));
    }
    function validate_package_name(&$package_name) {
        if (!$package_name) {
            exit_error("You must specify the name of the package with the --name parameter");
        }
        return true;
    }
    function validate_status_id(&$status_id) {
        if (!$status_id) {
            $status_id = 1;
        }
        return true;
    }
    function validate_rank(&$rank) {
        if (!$rank) {
            $rank = 1;
        }
        return true;
    }
    function validate_approve_license(&$approve_license) {
        $approve_license = $approve_license ? true : false;
        return true;
    }
}
