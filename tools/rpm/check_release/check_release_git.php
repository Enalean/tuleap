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

function getSystemOutput($cmd)
{
    $result;
    exec($cmd, $result);
    return $result;
}

function getCandidatePaths()
{
    $plugins     = getSystemOutput('find plugins -mindepth 1 -maxdepth 1 -type d');
    $themes      = getSystemOutput('find src/www/themes -mindepth 1 -maxdepth 1 -type d ! -path *common');
    $other_paths = array('src/www/soap');
    return array_merge($other_paths, $plugins, $themes);
}

require_once 'CheckReleaseGit.class.php';
require_once 'GitExec.class.php';

$git_exec   = new GitExec();
$tagFinder  = new LastReleaseFinder($git_exec);
$last_release_number = $tagFinder->retrieveFrom('stable');

$check_release_reporter = new CheckReleaseReporter(
    new NonIncrementedPathFinder(
        $git_exec,
        $last_release_number,
        new ChangeDetector($git_exec, getCandidatePaths())
    )
);
$check_release_reporter->reportViolations();
