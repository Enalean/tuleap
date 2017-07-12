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

class CLI_Action_Frs_DeleteEmptyRelease extends CLI_Action {

    function __construct() {
        parent::__construct('deleteEmptyRelease', 'Delete an empty release in a package.');
        $this->addParam(array(
            'name'           => 'package_id',
            'description'    => '--package_id=<package_id>    Id of the package in which empty release will be deleted.',
        ));
        $this->addParam(array(
            'name'           => 'release_id',
            'description'    => '--release_id=<release_id>    Id of the release to be deleted.',
        ));
    }

    function validate_package_id($package_id) {
        if (!$package_id) {
            exit_error("You must specify the ID of the package with the --package_id parameter");
        }
        return true;
    }

    function validate_release_id(&$release_id) {
        if (!$release_id) {
            exit_error("You must specify the ID of the release with the --release_id parameter");
        }
        return true;
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
