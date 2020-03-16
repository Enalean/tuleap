<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once __DIR__ . '/../../../src/www/include/pre.php';

if ($argc !== 7) {
    fwrite(STDERR, "Usage: {$argv[0]} user project_id tv3_id name description itemname" . PHP_EOL);
    exit(1);
}

$sys_user = getenv("USER");
if ($sys_user !== 'root' && $sys_user !== 'codendiadm') {
    fwrite(STDERR, 'Unsufficient privileges for user ' . $sys_user . PHP_EOL);
    exit(1);
}

$user           = $argv[1];
$project_id     = $argv[2];
$tv3_tracker_id = $argv[3];
$name           = $argv[4];
$description    = $argv[5];
$itemname       = $argv[6];

UserManager::instance()->forceLogin($user);
$project = ProjectManager::instance()->getProject($project_id);
if ($project->isError() || ! $project->isActive()) {
    fwrite(STDERR, 'ERROR: Project does not exist or is inactive' . PHP_EOL);
    exit(1);
}
$tv3 = new ArtifactType($project, $tv3_tracker_id);
if (! $tv3 || !is_object($tv3) || $tv3->isError() || ! $tv3->isValid()) {
    fwrite(STDERR, 'ERROR: Given tracker v3 does not exist or is invalid' . PHP_EOL);
    exit(1);
}
if (! $tv3->userCanView()) {
    fwrite(STDERR, 'ERROR: You cannot access the tracker ' . $tv3_tracker_id . PHP_EOL);
    exit(1);
}
$new_tracker = TrackerFactory::instance()->createFromTV3(UserManager::instance()->getCurrentUser(), $tv3_tracker_id, $project, $name, $description, $itemname);
if (! $new_tracker) {
    fwrite(STDERR, $GLOBALS['Response']->getRawFeedback());
    fwrite(STDERR, 'ERROR: Unable to migrate the tracker structure' . PHP_EOL);
    exit(1);
}

echo $new_tracker->getId();
