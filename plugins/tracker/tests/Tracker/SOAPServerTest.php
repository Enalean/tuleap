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

    public function setUp() {
        parent::setUp();
        $this->session_key    = 'zfsdfs65465';
        $this->tracker_id     = 1235;
        $this->int_field_name = 'int_field';

        $integer_field = anIntegerField()->withId(321)->isUsed()->build();

        $current_user        = stub('User')->isSuperUser()->returns(true);
        $user_manager        = stub('UserManager')->getCurrentUser($this->session_key)->returns($current_user);
        $tracker             = aMockTracker()->withId($this->tracker_id)->build();
        $permissions_manager = mock('PermissionsManager');

        $dao                 = mock('Tracker_ReportDao');
        stub($dao)->searchMatchingIds('*', $this->tracker_id, array($this->getFromForIntegerBiggerThan3()), '*', '*', '*', '*', '*', '*', '*')->returnsDar(
            array('id' => '42,66,9001', 'last_changeset_id' => '421,66,9001')
        );
        stub($dao)->searchMatchingIds()->returnsEmptyDar();

        $formelement_factory = mock('Tracker_FormElementFactory');
        stub($formelement_factory)->getFormElementByName($this->tracker_id, $this->int_field_name)->returns($integer_field);

        $tracker_factory = mock('TrackerFactory');
        stub($tracker_factory)->getTrackerById($this->tracker_id)->returns($tracker);

        $this->server = new Tracker_SOAPServer($user_manager, $tracker_factory, $permissions_manager, $dao, $formelement_factory);
    }

    private function getFromForIntegerBiggerThan3() {
        // Todo: find a way to not have to copy past this sql fragment
        return ' INNER JOIN tracker_changeset_value AS A_321 ON (A_321.changeset_id = c.id AND A_321.field_id = 321 )
                         INNER JOIN tracker_changeset_value_int AS B_321 ON (
                            B_321.changeset_value_id = A_321.id
                            AND B_321.value > 3
                         ) ';
    }

    public function itReturnsTheIdsOfTheArtifactsThatMatchTheQuery() {
        $criteria = array(
            array(
                'name'  => $this->int_field_name,
                'value' => '>3'
            ),
        );

        $artifacts_id = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, null, null);
        $this->assertEqual($artifacts_id, array(42, 66, 9001));
    }
}

?>
