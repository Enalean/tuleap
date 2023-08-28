<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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
 *
 */

session_write_close();

$sparklines        = [];
$reference_manager = ReferenceManager::instance();
$project_manager   = ProjectManager::instance();

$json = [];

$sparkline_urls = $request->get('sparklines');
if (is_array($sparkline_urls)) {
    foreach ($sparkline_urls as $url) {
        //Get sparkline parameters via the url
        $parameters = parse_url($url, PHP_URL_QUERY);
        parse_str($parameters, $sparkline);

        $vGroupId = new Valid_GroupId();
        $group_id = 100;
        if (isset($sparkline['group_id']) && $vGroupId->validate($sparkline['group_id'])) {
            $group_id = $sparkline['group_id'];
        }

        $v = new Valid_String();
        $v->required();

        if (isset($sparkline['key']) && isset($sparkline['val']) && $v->validate($sparkline['key']) && $v->validate($sparkline['val'])) {
            $key = $sparkline['key'];
            $val = $sparkline['val'];

            if ($key == 'wiki') {
                $args[] = $val;
            } else {
                $args = explode("/", $val);
            }

            //Get the reference
            $ref = $reference_manager->loadReferenceFromKeywordAndNumArgs($key, $group_id, count($args));

            if ($ref) {
                // Get groupname (might be useful in replace rules)
                $projname = null;
                $project  = $project_manager->getProject($group_id);
                if ($project) {
                    $projname = $project->getUnixName();
                }

                $ref->replaceLink($args, $projname);

                switch ($ref->getServiceShortName()) {
                    case 'tracker':
                    case 'svn':
                    case 'file':
                        break;

                    default:
                        $res_sparkline = '';
                        //Process to display the reference sparkline (ex: Hudson jobs)
                        $event_manager = EventManager::instance();
                        $event_manager->processEvent(
                            Event::AJAX_REFERENCE_SPARKLINE,
                            [
                                'reference' => $ref,
                                'keyword'  => $key,
                                'group_id' => $group_id,
                                'val'      => $val,
                                'sparkline' => &$res_sparkline,
                            ]
                        );
                        if ($res_sparkline) {
                            $json[$url] = $res_sparkline;
                        }
                }
            }
        }
    }
}

if (count($json)) {
    //handle JSON request if content
    header('Content-type: application/json');
    echo json_encode($json);
} else {
    header('HTTP/1.0 204 No Content');
    header('Status: 204');
    exit;
}
