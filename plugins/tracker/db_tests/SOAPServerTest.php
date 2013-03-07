<?php

/*
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

/**
 * Description of SOAPServerTest
 *
 * @author manuel
 */
/*class SOAPServerTest {
    //put your code here
}*/

require_once 'pre.php';
require_once dirname(__FILE__).'/../include/constants.php';
require_once dirname(__FILE__).'/../include/autoload.php';

$user_manager = UserManager::instance();
$_SERVER['REMOTE_ADDR'] = '127.0.01';
$user = $user_manager->login('manuel', 'manuel');

$formelement_factory = Tracker_FormElementFactory::instance();    
$soap_server = new Tracker_SOAPServer(
        new SOAP_RequestValidator(ProjectManager::instance(), $user_manager),
        TrackerFactory::instance(),
        PermissionsManager::instance(),
        new Tracker_ReportDao(),
        $formelement_factory,
        Tracker_ArtifactFactory::instance(),
        Tracker_ReportFactory::instance(),
        new Tracker_FileInfoFactory(new Tracker_FileInfoDao, $formelement_factory)
);

$values = array(
    (object) array(
        'field_name'  => 'cc_1',
        'field_label' => '',
        'field_value' => (object) array('value' => '')
    )
);
/*
$values = array(
    (object) array(
        'field_name'  => 'status_of_bugs',
        'field_label' => '',
        'field_value' => (object) array(
            'bind_value' => array(
                (object) array('bind_value_id' => 8387, 'bind_value_label' => '')
            )
        )
    )
);
*/
$soap_server->updateArtifact($user->getSessionHash(), 114, 278, 242, $values, '', 'text');


?>
