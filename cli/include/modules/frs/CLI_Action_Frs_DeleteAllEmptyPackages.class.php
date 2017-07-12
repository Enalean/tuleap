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

class CLI_Action_Frs_DeleteAllEmptyPackages extends CLI_Action {

    function __construct() {
        parent::__construct('deleteAllEmptyPackages', 'Delete all empty packages in a project.');
    }

    function soapCall($soap_params, $use_extra_params = true) {
        $soap_params['package_id'] = 0;
        $soap_params['cleanup_all'] = 1;
        return $GLOBALS['soap']->call('deleteEmptyPackage', $soap_params, $use_extra_params);
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
