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
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once(CODENDI_CLI_DIR.'/CLI_Action.class.php');

class CLI_Action_Frs_DeleteAllEmptyReleases extends CLI_Action {

    function __construct() {
        parent::__construct('deleteAllEmptyReleases', 'Delete all empty releases in a package.');
        $this->addParam(array(
            'name'           => 'package_id',
            'description'    => '--package_id=<package_id>    Id of the package in which empty releases will be deleted.',
        ));
    }

    function validate_package_id($package_id) {
        if (!$package_id) {
            exit_error("You must specify the ID of the package with the --package_id parameter");
        }
        return true;
    }

    function soapCall($soap_params, $use_extra_params = true) {
        $soap_params['release_id'] = 0;
        $soap_params['cleanup_all'] = 1;
        return $GLOBALS['soap']->call('deleteEmptyRelease', $soap_params, $use_extra_params);
    }

    function soapResult($params, $soap_result, $fieldnames = array(), $loaded_params = array()) {
        if (!$loaded_params['others']['quiet']) {
            if (empty($soap_result)) {
                echo "No release were deleted\n";
            } else {
                $this->show_output($soap_result, $fieldnames);
            }
        }
    }

}
