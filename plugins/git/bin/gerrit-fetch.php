<?php
/**
 * Copyright (c) Enalean, 2012. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once('pre.php');

$repository = new GitRepository();
$repository->getFullPath();
        //
        //get all repos migrated to gerrit
        //
        //update all branches
        //
        // cd repo/directory
        //     && git fetch gerrit -q 
        //     && git update-ref refs/heads/master refs/remotes/gerrit/master
//
//
//
//
//
//
//



?>
