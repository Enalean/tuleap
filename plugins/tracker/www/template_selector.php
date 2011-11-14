<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'pre.php';
require_once 'common/include/GroupFactory.class.php';
require_once 'common/include/Combined.class.php';
require_once dirname(__FILE__).'/../include/Tracker/TrackerFactory.class.php';

//$res = array();

$vFunc = new Valid_WhiteList('func', array('plugin_tracker'));
$vFunc->required();
if ($request->isPost() && $request->valid($vFunc)) {
    switch ($request->get('func')) {
        case 'plugin_tracker':
            $trackerFactory = TrackerFactory::instance();
            
            $pm = ProjectManager::instance();
            if ($request->existAndNonEmpty('target')) {
                $group_id = $request->get('target');
                $project = $pm->getProject($group_id);
            } else {
                $project = $pm->getProjectFromAutocompleter($request->get('target_name'));
            }
            if ($project && !$project->isError() && ($project->isActive() || $project->getId() == 100)) {
                $trackers = $trackerFactory->getTrackersByGroupId($project->getID());
                if (count($trackers) > 0) {
                    foreach ($trackers as $tracker) {
                        //$res[] = array('id' => $tracker->getId(), 'name' => $tracker->getName());
                        echo '<option value="'.$tracker->getId().'">'.$tracker->getName().'</option>';
                    }
                } else {
                    echo '<option><em>No tracker found</em></option>';
                }
            } else {
                echo '<option><em>No tracker found</em></option>';
            }
            break;
    }
}
/*
if (count($res)) {
    //handle JSON request if content
    header('Content-type: application/json');
    echo json_encode($res);
} else {
    header('HTTP/1.0 204 No Content');
    header('Status: 204');
    exit;
}
*/
?>