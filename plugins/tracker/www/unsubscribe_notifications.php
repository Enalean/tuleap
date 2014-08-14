<?php
/**
 * Copyright (c) STMicroelectronics 2012. All rights reserved
 * Copyright (c) Enalean 2014. All rights reserved
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

require_once('pre.php');

$valid = new Valid_UInt('artifact');
$valid->required();

if ($request->valid($valid)) {
    $user             = $request->getCurrentUser();
    $artifact_id      = $request->get('artifact');
    $artifact_factory = Tracker_ArtifactFactory::instance();
    $dao              = $artifact_factory->getDao();
    $user_unsubscribe = $dao->doesUserHaveUnsubscribedFromNotifications($artifact_id, $user->getId());

    $response = array();

    if ($user_unsubscribe) {
        $dao->deleteUnsubscribeNotification($artifact_id, $user->getId());
        $response["notification"] = true;

    } else {
        $dao->createUnsubscribeNotification($artifact_id, $user->getId());
        $response["notification"] = false;
    }

    $GLOBALS['Response']->sendJSON($response);

    return true;
}