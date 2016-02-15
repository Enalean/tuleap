<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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

namespace Tuleap\PullRequest;

use Git_Command_Exception;
use Git_Exec;

class GitExec extends Git_Exec {

    public function getReferenceBranch($branch_name) {
        $output = array();

        try {
            $this->gitCmdWithOutput('show-ref --hash refs/heads/' . escapeshellarg($branch_name), $output);
        } catch (Git_Command_Exception $exception) {
            throw new UnknownReferenceBranchException($branch_name, 0, $exception);
        }

        if (count($output) === 1) {
            return $output[0];
        }

        throw new UnknownReferenceBranchException($branch_name);
    }
}