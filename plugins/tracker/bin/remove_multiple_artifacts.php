<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletorBuilder;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\DeletionContext;
use Tuleap\User\PasswordVerifier;

require_once __DIR__ . '/../../../src/www/include/pre.php';

if ($argc !== 5) {
    fwrite(STDERR, "Usage: {$argv[0]} user_name tracker_id first_artifact_id last_artifact_id" . PHP_EOL);
    exit(1);
}

$sys_user = getenv("USER");
if ($sys_user !== 'root' && $sys_user !== 'codendiadm') {
    fwrite(STDERR, 'Unsufficient privileges for user ' . $sys_user . PHP_EOL);
    exit(1);
}

$user_name         = $argv[1];
$tracker_id        = $argv[2];
$first_artifact_id = $argv[3];
$last_artifact_id  = $argv[4];
$password          = null;

if (! isset($password)) {
    echo "Password for $user_name: ";

    if (PHP_OS != 'WINNT') {
        shell_exec('stty -echo');
        $password = fgets(STDIN);
        shell_exec('stty echo');
    } else {
        $password = fgets(STDIN);
    }
    $password = substr($password, 0, strlen($password) - 1);
    echo PHP_EOL;
}

$password_handler = PasswordHandlerFactory::getPasswordHandler();

$login_manager = new User_LoginManager(
    EventManager::instance(),
    UserManager::instance(),
    new PasswordVerifier($password_handler),
    new User_PasswordExpirationChecker(),
    PasswordHandlerFactory::getPasswordHandler()
);

try {
    $tuleap_user = $login_manager->authenticate($user_name, new \Tuleap\Cryptography\ConcealedString($password));
} catch (Exception $exception) {
    fwrite(STDERR, 'Login or password invalid. Exit' . PHP_EOL);
    exit(1);
}

$tracker = TrackerFactory::instance()->getTrackerById($tracker_id);

if (! $tracker) {
    fwrite(STDERR, 'Tracker id does not exist' . PHP_EOL);
    exit(1);
}

if (! $tracker->userIsAdmin($tuleap_user)) {
    fwrite(STDERR, $user_name . ' is not administrator of Tracker #' . $tracker_id . '. Exit.' . PHP_EOL);
    exit(1);
}

$artifact_deletor = ArtifactDeletorBuilder::build();

$current_artifact_id = $first_artifact_id;

while ($current_artifact_id <= $last_artifact_id) {
    $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($current_artifact_id);

    if (! $artifact) {
        fwrite(STDERR, 'Artifact #' . $current_artifact_id . ' not found. Continuing remove other artifacts.' . PHP_EOL);
        $current_artifact_id++;
        continue;
    }

    if ($artifact->getTrackerId() != $tracker_id) {
        fwrite(
            STDERR,
            'Artifact #' . $current_artifact_id . ' is not in Tracker #' . $tracker_id . ' . Continuing remove other artifacts.' . PHP_EOL
        );
        $current_artifact_id++;
        continue;
    }

    $project_id = (int) $artifact->getTracker()->getGroupId();

    fwrite(STDOUT, 'Removing artifact #' . $current_artifact_id . PHP_EOL);
    $artifact_deletor->delete($artifact, $tuleap_user, DeletionContext::regularDeletion($project_id));
    $current_artifact_id++;
}
