<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once('pre.php');

$sparklines = array();
$reference_manager = ReferenceManager::instance();
$json = array();

foreach ($request->get('sparklines') as $url) {
   $parameters = parse_url($url, PHP_URL_QUERY);
   $parameters = explode('&', $parameters);
   $sparkline = array();
   foreach ($parameters as $parameter) {
       $parameter = explode('=', $parameter);
       $sparkline[$parameter[0]] = $parameter[1];
   }
   
   if ($sparkline['key'] == 'wiki') {
        $args[]= $sparkline['val'];
    } else {
        $args = explode("/", $sparkline['val']);
    }
    
    $ref = $reference_manager->loadReferenceFromKeywordAndNumArgs($sparkline['key'], $sparkline['group_id'], count($args));
    switch($ref->getServiceShortName()) {
        case 'tracker':
        case 'svn':
        case 'cvs':
        case 'file':
            break;
        default:
            $res = '';
            $em->processEvent(Event::AJAX_REFERENCE_SPARKLINE, array(
                'reference'=> $ref,
                'keyword'  => $sparkline['key'],
                'group_id' => $sparkline['group_id'],
                'val'      => $sparkline['val'],
                'sparkline'=> &$res,
            ));
            $json[$url] = $res;            
    }
}

if (count($json)) {
    header('Content-type: application/json');
    echo json_encode($json);
} else {
    header('HTTP/1.0 204 No Content');
    header('Status: 204');
    exit;
}

?>
