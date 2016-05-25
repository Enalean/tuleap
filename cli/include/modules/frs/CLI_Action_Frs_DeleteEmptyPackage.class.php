<?php
/**
 * Copyright (c) STMicroelectronics 2011. All rights reserved
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_DeleteEmptyPackage extends CLI_Action {

    function __construct() {
        parent::__construct('deleteEmptyPackage', 'Delete an empty package in a project.');
        $this->addParam(array(
            'name'           => 'package_id',
            'description'    => '--package_id=<package_id>    Id of the package to be deleted.',
        ));
    }

    function validate_package_id(&$package_id) {
        if (!$package_id) {
            exit_error("You must specify the ID of the package with the --package_id parameter");
        }
        return true;
    }

    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        if (!$loaded_params['others']['quiet']) {
            if (empty($soap_result)) {
                echo "No package were deleted\n";
            } else {
                $this->show_output($soap_result, $fieldnames);
            }
        }
    }

}
