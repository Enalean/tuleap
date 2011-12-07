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

class CLI_Action_Frs_DeleteEmptyPackages extends CLI_Action {

    function CLI_Action_Frs_DeleteEmptyPackages() {
        $this->CLI_Action('deleteEmptyPackages', 'Delete empty packages in a project.');
    }

}

?>