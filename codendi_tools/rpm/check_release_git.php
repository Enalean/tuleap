<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

$tagFinder = new GitTagFinder(new GitExec());
$maxVersion = $tagFinder->getMaxVersionFrom('origin');

echo "latest version : $maxVersion".PHP_EOL;

$change_detector = new GitChangeDetector(new GitExec());
$changed_paths = $change_detector->retainPathsThatHaveChanged($candidate_paths, $maxVersion);

$version_increment_filter = new VersionIncrementFilter(new GitExec());
$non_incremented_paths = $version_increment_filter->keepPathsThatHaventBeenIncremented($changed_paths, $maxVersion, $new_revision);

$check_release_reporter = new CheckReleaseReporter();
$check_release_reporter->reportOn($non_incremented_paths);
?>