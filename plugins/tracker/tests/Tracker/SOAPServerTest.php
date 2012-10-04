<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../builders/all.php');
require_once TRACKER_BASE_DIR.'/Tracker/SOAPServer.class.php';

class Tracker_SOAPServer_CriteriaTransformTest extends TuleapTestCase {

    public function itConvertsFloat() {
        $session_key = 'zfsdfs65465';
        $tracker_id = 1235;
        $field_name = 'float_field';
        $criteria = array(
            array('name'  => $field_name,
                  'value' => '>3'),
        );

        $integer_field = anIntegerField()->withId(321)->isUsed()->build();

        $current_user        = stub('User')->isSuperUser()->returns(true);
        $user_manager        = stub('UserManager')->getCurrentUser($session_key)->returns($current_user);
        $tracker             = aMockTracker()->withId($tracker_id)->build();
        $permissions_manager = mock('PermissionsManager');

        $dao                 = mock('Tracker_ReportDao');
        stub($dao)->searchMatchingIds()->returnsEmptyDar();

        $formelement_factory = mock('Tracker_FormElementFactory');
        stub($formelement_factory)->getFormElementByName($tracker_id, $field_name)->returns($integer_field);

        $tracker_factory = mock('TrackerFactory');
        stub($tracker_factory)->getTrackerById($tracker_id)->returns($tracker);

        $server = new Tracker_SOAPServer($user_manager, $tracker_factory, $permissions_manager, $dao, $formelement_factory);
        $server->getArtifacts($session_key, null, $tracker_id, $criteria, null, null);
    }
}

?>
