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

class Tracker_SOAPServer_BaseTest extends TuleapTestCase {

    protected $session_key           = 'zfsdfs65465';
    protected $tracker_id            = 1235;
    protected $unreadable_tracker_id = 5321;
    protected $int_field_name        = 'int_field';

    public function setUp() {
        parent::setUp();

        $integer_field = anIntegerField()->withId(321)->isUsed()->build();

        $current_user        = mock('User');
        stub($current_user)->isSuperUser()->returns(true);
        stub($current_user)->isLoggedIn()->returns(true);
        $user_manager        = stub('UserManager')->getCurrentUser($this->session_key)->returns($current_user);
        $project_manager     = mock('ProjectManager');
        $permissions_manager = mock('PermissionsManager');
        $artifact_factory    = mock('Tracker_ArtifactFactory');

        $dao                 = mock('Tracker_ReportDao');
        stub($dao)->searchMatchingIds('*', $this->tracker_id, array($this->getFromForIntegerBiggerThan3()), '*', '*', '*', '*', '*', '*', '*')->returnsDar(
            array('id' => '42,66,9001', 'last_changeset_id' => '421,66,9001')
        );
        stub($dao)->searchMatchingIds()->returnsEmptyDar();

        $formelement_factory = mock('Tracker_FormElementFactory');
        stub($formelement_factory)->getFormElementByName($this->tracker_id, $this->int_field_name)->returns($integer_field);

        $tracker_factory = mock('TrackerFactory');
        $this->setUpTrackers($tracker_factory);

        $this->server = new Tracker_SOAPServer(
            new SOAP_UserManager($user_manager),
            $project_manager,
            $tracker_factory,
            $permissions_manager,
            $dao,
            $formelement_factory,
            $artifact_factory
        );
    }

    private function setUpTrackers(TrackerFactory $tracker_factory) {
        $tracker            = aMockTracker()->withId($this->tracker_id)->build();
        $unreadable_tracker = aMockTracker()->withId($this->unreadable_tracker_id)->build();
        stub($tracker)->userCanView()->returns(true);
        stub($unreadable_tracker)->userCanView()->returns(false);
        stub($tracker_factory)->getTrackerById($this->tracker_id)->returns($tracker);
        stub($tracker_factory)->getTrackerById($this->unreadable_tracker_id)->returns($unreadable_tracker);
    }

    private function getFromForIntegerBiggerThan3() {
        // Todo: find a way to not have to copy past this sql fragment
        return ' INNER JOIN tracker_changeset_value AS A_321 ON (A_321.changeset_id = c.id AND A_321.field_id = 321 )
                         INNER JOIN tracker_changeset_value_int AS B_321 ON (
                            B_321.changeset_value_id = A_321.id
                            AND B_321.value > 3
                         ) ';
    }
}

class Tracker_SOAPServer_getArtifacts_Test extends Tracker_SOAPServer_BaseTest {

    public function itRaisesASoapFaultIfTheTrackerIsNotReadableByTheUser() {
        $this->expectException('SoapFault');
        $this->server->getArtifacts($this->session_key, null, $this->unreadable_tracker_id, array(), null, null);
    }

    public function itReturnsTheIdsOfTheArtifactsThatMatchTheQuery() {
        $criteria = array(
            array(
                'name'  => $this->int_field_name,
                'value' => (
                    array('value' => '>3')
                )
            ),
        );

        $artifacts_id = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, null, null);
        $this->assertEqual($artifacts_id, array(42, 66, 9001));
    }
}

?>
