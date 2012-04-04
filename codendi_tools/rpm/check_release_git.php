<?php
/**
 * Copyright (c) Enalean, 2010. All Rights Reserved.
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

function getSystemOutput($cmd) {
    $result;
    exec($cmd, $result);
    return $result;
}

require_once 'CheckReleaseGit.class.php';
require_once 'GitExec.class.php';


echo "Please check documentation/cli and documentation/user_guide manually!!".PHP_EOL;
$plugins = getSystemOutput('find plugins -type d -depth 1');
$themes = getSystemOutput('find src/www/themes -type d -depth 1 ! -path *common');
$other_paths = array('cli', 'src/www/soap');
$candidate_paths = array_merge($other_paths, $plugins, $themes);

$new_revision = 'HEAD';

$releaseChecker = new CheckReleaseGit(new GitExec());
$tagFinder = new GitTagFinder(new GitExec());
$versions = $tagFinder->getVersionList();
$maxVersion = $tagFinder->maxVersion($versions);

echo "latest version : $maxVersion".PHP_EOL;

$changed_paths = $releaseChecker->retainPathsThatHaveChanged($candidate_paths, $maxVersion);
$non_incremented_paths = $releaseChecker->keepPathsThatHaventBeenIncremented($changed_paths, $maxVersion, $new_revision);


$COLOR_RED     = "\033[31m";
$COLOR_GREEN   = "\033[32m";
$COLOR_NOCOLOR = "\033[0m";
foreach ($non_incremented_paths as $non_incremented_path) {
    echo "$COLOR_RED $non_incremented_path changed but wasn't incremented $COLOR_NOCOLOR".PHP_EOL;
}

if (! $non_incremented_paths) {
    echo "$COLOR_GREEN Everything was incremented correctly $COLOR_NOCOLOR".PHP_EOL;
}

exit(count($non_incremented_paths));
?>