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

class CLI_Action_Frs_DeletePackage extends CLI_Action {

    function CLI_Action_Frs_DeletePackage() {
        $this->CLI_Action('deletePackage', 'Delete an empty package or all empty packages in a project.');
        $this->addParam(array(
            'name'           => 'package_id',
            'description'    => '--package_id=<package_id>    (Optional)Id of the package to be deleted, keep empty if you want to remove all empty packages of the project.',
        ));
    }

    function validate_package_id(&$package_id) {
        if (!$package_id) {
            $package_id = 0;
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

?>