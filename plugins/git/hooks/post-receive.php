#!/usr/bin/php
<?php
/**
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 * 
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

// Backend scripts should never ends because of lack of time or memory
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

require_once dirname(__FILE__).'/../include/bootstrap.php';

$repository_path = $argv[1];
$user_name       = $argv[2];
$old_rev         = $argv[3];
$new_rev         = $argv[4];
$refname         = $argv[5];

$post_receive = new Git_Hook_PostReceive(new Git_Exec($repository_path, $repository_path), new Git_Hook_ExtractCrossReferences());
$post_receive->execute($repository_path, $user_name, $old_rev, $new_rev, $refname);

?>
