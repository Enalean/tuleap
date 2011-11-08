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

$vFunc = new Valid_WhiteList('func', array('plugin_tracker'));
$vFunc->required();
if ($request->isPost() && $request->valid($vFunc)) {
    switch ($request->get('func')) {
        case 'plugin_tracker':
            $trackerFactory = TrackerFactory::instance();
            $trackers = $trackerFactory->getTrackersByGroupId($request->get('target'));
            if (count($trackers)) {
                foreach ($trackers as $tracker) {
                    echo '<li class="tracker_selector_tracker" rel="'.$tracker->getId().'">'.$tracker->getName().'</li>';
                }
            } else {
                echo '<li><em>No tracker found</em></li>';
            }
            break;
    }
    exit;
}
/*
$gf = new GroupFactory();
$hp = Codendi_HTMLPurifier::instance();

echo '<style>.highlight_list_element { background-color: yellow; }</style>';

echo '<ul style="border: 1px solid grey; width: 20em; float: left;">';
$results = $gf->getMemberGroups();
while ($row = db_fetch_array($results)) {
    echo '<li class="tracker_selected_project" rel="'.$hp->purify($row['group_id']).'">'.$hp->purify($row['group_name']).'</li>';
}
echo '</ul>';

echo '<ul style="border: 1px solid grey; width: 20em; float: right;" id="tracker_list_trackers_from_project">';
echo '<li>select a project first</li>';
echo '</ul>';

$combined = new Combined();
echo $combined->getScripts(array('/scripts/codendi/common.js'));
echo '<script type="text/javascript" src="/plugins/tracker/scripts/TrackerTemplateSelector.js"></script>';
*/
?>