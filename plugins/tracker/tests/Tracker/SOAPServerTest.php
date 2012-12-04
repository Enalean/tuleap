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
    /**
     * @var Tracker_SOAPServer
     */
    protected $server;
    protected $session_key           = 'zfsdfs65465';
    protected $user_id               = 9876;
    protected $tracker_id            = 1235;
    protected $tracker;
    protected $unreadable_tracker_id = 5321;
    protected $unreadable_tracker;
    protected $int_field_name        = 'int_field';
    protected $date_field_name       = 'date_field';
    protected $list_field_name       = 'list_field';
    protected $expected_artifact_42  = array(
        'artifact_id'       => 42,
        'tracker_id'        => 1235,
        'submitted_by'      => '',
        'submitted_on'      => '',
        'last_update_date'  => '',
        'value'             => array(),
    );
    protected $expected_artifact_66 = array(
        'artifact_id'       => 66,
        'tracker_id'        => 1235,
        'submitted_by'      => '',
        'submitted_on'      => '',
        'last_update_date'  => '',
        'value'             => array(),
    );
    protected $expected_artifact_9001 = array(
        'artifact_id'       => 9001,
        'tracker_id'        => 1235,
        'submitted_by'      => '',
        'submitted_on'      => '',
        'last_update_date'  => '',
        'value'             => array(),
    );
    protected $artifact_factory;
    protected $formelement_factory;
    protected $report_factory;

    public function setUp() {
        parent::setUp();

        $current_user        = mock('User');
        stub($current_user)->getId()->returns($this->user_id);
        stub($current_user)->isSuperUser()->returns(true);
        stub($current_user)->isLoggedIn()->returns(true);
        $user_manager        = stub('UserManager')->getCurrentUser($this->session_key)->returns($current_user);
        $project_manager     = mock('ProjectManager');
        $permissions_manager = mock('PermissionsManager');

        $this->artifact_factory    = mock('Tracker_ArtifactFactory');
        $this->setUpArtifacts($this->artifact_factory);

        $dao = mock('Tracker_ReportDao');
        $this->setUpArtifactResults($dao);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->setUpFields($this->formelement_factory);

        $tracker_factory = mock('TrackerFactory');
        $this->setUpTrackers($tracker_factory);

        $soap_user_manager = mock('SOAP_UserManager');
        stub($soap_user_manager)->continueSession($this->session_key)->returns($current_user);

        $this->report_factory = mock('Tracker_ReportFactory');

        $this->server = new Tracker_SOAPServer(
            $soap_user_manager,
            $project_manager,
            $tracker_factory,
            $permissions_manager,
            $dao,
            $this->formelement_factory,
            $this->artifact_factory,
            $this->report_factory
        );
    }

    private function setUpArtifacts(Tracker_ArtifactFactory $artifact_factory) {
        $changesets = array(stub('Tracker_Artifact_Changeset')->getValues()->returns(array()));
        $artifact_42   = anArtifact()->withId(42)->withTrackerId($this->tracker_id)->withChangesets($changesets)->build();
        $artifact_66   = anArtifact()->withId(66)->withTrackerId($this->tracker_id)->withChangesets($changesets)->build();
        $artifact_9001 = anArtifact()->withId(9001)->withTrackerId($this->tracker_id)->withChangesets($changesets)->build();
        stub($artifact_factory)->getArtifactById(42)->returns($artifact_42);
        stub($artifact_factory)->getArtifactById(66)->returns($artifact_66);
        stub($artifact_factory)->getArtifactById(9001)->returns($artifact_9001);
    }

    private function setUpFields(Tracker_FormElementFactory $formelement_factory) {
        $list_field    = aSelectboxField()->withId(323)->isUsed()->build();
        $date_field    = aDateField()->withId(322)->isUsed()->build();
        $integer_field = anIntegerField()->withId(321)->isUsed()->build();

        $static_bind = aBindStatic()->withField($list_field)->build();
        $list_field->setBind($static_bind);

        stub($formelement_factory)->getFormElementByName($this->tracker_id, $this->list_field_name)->returns($list_field);
        stub($formelement_factory)->getFormElementByName($this->tracker_id, $this->date_field_name)->returns($date_field);
        stub($formelement_factory)->getFormElementByName($this->tracker_id, $this->int_field_name)->returns($integer_field);
    }

    private function setUpTrackers(TrackerFactory $tracker_factory) {
        $this->tracker      = aMockTracker()->withId($this->tracker_id)->build();
        $this->unreadable_tracker = aMockTracker()->withId($this->unreadable_tracker_id)->build();
        stub($this->tracker)->userCanView()->returns(true);
        stub($this->tracker)->userIsAdmin()->returns(true);
        stub($this->unreadable_tracker)->userCanView()->returns(false);
        stub($tracker_factory)->getTrackerById($this->tracker_id)->returns($this->tracker);
        stub($tracker_factory)->getTrackerById($this->unreadable_tracker_id)->returns($this->unreadable_tracker);
    }

    private function setUpArtifactResults(Tracker_ReportDao $dao) {
        stub($dao)->searchMatchingIds('*', $this->tracker_id, $this->getFromForIntegerBiggerThan3(), '*', '*', '*', '*', '*', '*', '*')->returnsDar(
            array('id' => '42,66,9001', 'last_changeset_id' => '421,661,90011')
        );
        stub($dao)->searchMatchingIds('*', $this->tracker_id, $this->getFromForDateFieldEqualsTo(), '*', '*', '*', '*', '*', '*', '*')->returnsDar(
            array('id' => '9001', 'last_changeset_id' => '90011')
        );
        stub($dao)->searchMatchingIds('*', $this->tracker_id, $this->getFromForDateFieldAdvanced(), '*', '*', '*', '*', '*', '*', '*')->returnsDar(
            array('id' => '42,9001', 'last_changeset_id' => '421,90011')
        );
        stub($dao)->searchMatchingIds('*', $this->tracker_id, $this->getFromForListField(), '*', '*', '*', '*', '*', '*', '*')->returnsDar(
            array('id' => '42', 'last_changeset_id' => '421')
        );
        stub($dao)->searchMatchingIds('*', $this->tracker_id, $this->getFromForListFieldAdvanced(), '*', '*', '*', '*', '*', '*', '*')->returnsDar(
            array('id' => '42,66', 'last_changeset_id' => '421,661')
        );
        stub($dao)->searchMatchingIds()->returnsDar(
            array('id' => null, 'last_changeset_id' => null)
        );
    }

    private function getFromForIntegerBiggerThan3() {
        return new FromFragmentsExpectation(array('/ tracker_changeset_value_int AS ..321.* ..321.value > 3/s'));
    }

    private function getFromForDateFieldEqualsTo() {
        return new FromFragmentsExpectation(array('/ tracker_changeset_value_date AS ..322.* ..'.
            preg_quote('322.value BETWEEN 12334567') .'\s* '. preg_quote('AND 12334567 + 24 * 60 * 60') .'/s'));
    }

    private function getFromForDateFieldAdvanced() {
        return new FromFragmentsExpectation(array('/ tracker_changeset_value_date AS ..322.* ..'.
            preg_quote('322.value BETWEEN 1337') .'\s* '. preg_quote('AND 1338 + 24 * 60 * 60') .'/s'));
    }

    private function getFromForListField() {
        return new FromFragmentsExpectation(array('/ tracker_changeset_value_list AS ..323.* ..'.
            preg_quote('323.bindvalue_id IN(106)') .'/s'));
    }

    private function getFromForListFieldAdvanced() {
        return new FromFragmentsExpectation(array('/ tracker_changeset_value_list AS ..323.* ..'.
            preg_quote('323.bindvalue_id IN(106,107)') .'/s'));
    }

    protected function convertCriteriaToSoapParameter($criteria) {
        //SOAP send objects, not associative array.
        //Use json as a trick to convert to objects the criteria
        return json_decode(json_encode($criteria));
    }
}

class FromFragmentsExpectation extends SimpleExpectation {

    /**
     * @var array of pattern
     */
    private $expected_fragments;

    public function __construct(array $expected_fragments) {
        $this->expected_fragments = $expected_fragments;
    }

    public function test($fragments) {
        if (count($fragments) !== count($this->expected_fragments)) {
            return false;
        }
        foreach ($fragments as $i => $fragment) {
            if (!preg_match($this->expected_fragments[$i], $fragment)) {
                return false;
            }
        }
        return true;
    }

    public function testMessage($fragments) {
        if (count($fragments) !== count($this->expected_fragments)) {
            return 'Number of fragments differ ('. count($fragments) .' expected: '. count($this->expected_fragments) .')';
        }
        foreach ($fragments as $i => $fragment) {
            if (!preg_match($this->expected_fragments[$i], $fragment)) {
                return "Fragment #$i [$fragment] does not match [{$this->expected_fragments[$i]}]";
            }
        }
    }
}
/*
class Tracker_SOAPServer_getArtifacts_Test extends Tracker_SOAPServer_BaseTest {

    public function itRaisesASoapFaultIfTheTrackerIsNotReadableByTheUser() {
        $this->expectException('SoapFault');
        $this->server->getArtifacts($this->session_key, null, $this->unreadable_tracker_id, array(), null, null);
    }

    public function itReturnsEmptyResultsWhenThereIsNoMatch() {
        $criteria = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name' => $this->int_field_name,
                'value'      => array('value' => 'A value that returns empty results')
            ),
        ));

        $results = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, 0, 10);
        $this->assertEqual($results, array(
            'total_artifacts_number' => 0,
            'artifacts' => array(
            )
        ));
    }

    public function itReturnsTheArtifactsThatMatchTheQueryForAnIntegerField() {
        $criteria = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name' => $this->int_field_name,
                'value'      => array('value' => '>3')
            ),
        ));

        $results = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, 0, 10);
        $this->assertEqual($results, array(
            'total_artifacts_number' => 3,
            'artifacts' => array(
                $this->expected_artifact_42,
                $this->expected_artifact_66,
                $this->expected_artifact_9001,
            )
        ));
    }

    public function itPaginatesFromTheStart() {
        $criteria = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name' => $this->int_field_name,
                'value'      => array('value' => '>3')
            ),
        ));

        $results = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, 0, 2);
        $this->assertEqual($results, array(
            'total_artifacts_number' => 3,
            'artifacts' => array(
                $this->expected_artifact_42,
                $this->expected_artifact_66,
            )
        ));
    }

    public function itContinuesPagination() {
        $criteria = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name' => $this->int_field_name,
                'value'      => array('value' => '>3')
            ),
        ));

        $results = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, 2, 2);
        $this->assertEqual($results, array(
            'total_artifacts_number' => 3,
            'artifacts' => array(
                $this->expected_artifact_9001,
            )
        ));
    }

    public function itReturnsTheArtifactsThatMatchTheQueryForADateField() {
        $criteria = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name' => $this->date_field_name,
                'value'      => array(
                    'date' => array('op' => '=', 'to_date' => '12334567')
                )
            ),
        ));

        $results = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, 0, 10);
        $this->assertEqual($results, array(
            'total_artifacts_number' => 1,
            'artifacts' => array(
                $this->expected_artifact_9001,
            )
        ));
    }

    public function itReturnsTheArtifactsThatMatchTheAdvancedQueryForADateField() {
        $criteria = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name' => $this->date_field_name,
                'value'      => array(
                    'dateAdvanced' => array('from_date' => '1337', 'to_date' => '1338')
                )
            ),
        ));

        $results = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, 0, 10);
        $this->assertEqual($results, array(
            'total_artifacts_number' => 2,
            'artifacts' => array(
                $this->expected_artifact_42,
                $this->expected_artifact_9001,
            )
        ));
    }

    public function itReturnsTheArtifactsThatMatchTheQueryForAListField() {
        $criteria = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name' => $this->list_field_name,
                'value'      => array('value' => '106')
            ),
        ));

        $results = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, 0, 10);
        $this->assertEqual($results, array(
            'total_artifacts_number' => 1,
            'artifacts' => array(
                $this->expected_artifact_42,
            )
        ));
    }

    public function itReturnsTheArtifactsThatMatchTheAdvancedQueryForAListField() {
        $criteria = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name' => $this->list_field_name,
                'value'      => array('value' => '106,107')
            ),
        ));

        $results = $this->server->getArtifacts($this->session_key, null, $this->tracker_id, $criteria, 0, 10);
        $this->assertEqual($results, array(
            'total_artifacts_number' => 2,
            'artifacts' => array(
                $this->expected_artifact_42,
                $this->expected_artifact_66,
            )
        ));
    }
}

class Tracker_SOAPServer_getTrackerReports_Test extends Tracker_SOAPServer_BaseTest {

    public function itRaisesAnExceptionWhenTheTrackerIsNotReadableByUser() {
        $this->expectException('SoapFault');
        $this->server->getTrackerReports($this->session_key, null, $this->unreadable_tracker_id);
    }

    public function itGetTheReportsFromTheUnderlyingAPI() {
        expect($this->report_factory)->getReportsByTrackerId($this->tracker_id, $this->user_id)->once();
        stub($this->report_factory)->getReportsByTrackerId()->returns(array());
        $this->server->getTrackerReports($this->session_key, null, $this->tracker_id);
    }

    public function itTransformTheReportIntoASoapResponse() {
        $report = mock('Tracker_Report');
        expect($report)->exportToSoap()->once();
        stub($this->report_factory)->getReportsByTrackerId()->returns(
            array(
                100 => $report
            )
        );
        $this->server->getTrackerReports($this->session_key, null, $this->tracker_id);
    }

    public function itReturnsTheSOAPVersionOfTheReport() {
        $soap_of_one_report = array('id' => 100);
        stub($this->report_factory)->getReportsByTrackerId()->returns(
            array(
                100 => stub('Tracker_Report')->exportToSoap()->returns($soap_of_one_report)
            )
        );
        $soap_response = $this->server->getTrackerReports($this->session_key, null, $this->tracker_id);
        $this->assertEqual($soap_response, array($soap_of_one_report));
    }
}

class Tracker_SOAPServer_getTrackerReportArtifacts_Test extends Tracker_SOAPServer_BaseTest {

    public function setUp() {
        parent::setUp();
        $this->report_id = 987;
        $this->report = mock('Tracker_Report');
        stub($this->report)->getTracker()->returns($this->tracker);
    }

    public function itRaisesAnExceptionWhenReportIsPublicButTheTrackerIsNotReadableByUser() {
        $report_id = 987;
        $report = aTrackerReport()->withTracker($this->unreadable_tracker)->build();
        stub($this->report_factory)->getReportById($report_id, $this->user_id, false)->returns($report);
        $this->expectException('SoapFault');
        $this->server->getArtifactsFromReport($this->session_key, $report_id, 0, 10);
    }

    public function itRaisesAnExceptionWhenThereIsNoReportMatching() {
        $report_id = 987;
        stub($this->report_factory)->getReportById()->returns(null);
        $this->expectException('SoapFault');
        $this->server->getArtifactsFromReport($this->session_key, $report_id, 0, 10);
    }

    public function itGetsMatchingIdsFromReport() {
        expect($this->report)->getMatchingIds(null, true)->once();
        stub($this->report_factory)->getReportById()->returns($this->report);
        $this->server->getArtifactsFromReport($this->session_key, $this->report_id, 0, 10);
    }

    public function itConvertsMatchingIdsIntoAnArrayOfInteger() {
        stub($this->report)->getMatchingIds()->returns(array('id' => '42,66,9001', 'last_changeset_id' => '421,661,90011'));

        stub($this->report_factory)->getReportById()->returns($this->report);
        $soap_response = $this->server->getArtifactsFromReport($this->session_key, $this->report_id, 0, 10);
        $this->assertEqual($soap_response, array(
            'total_artifacts_number' => 3,
            'artifacts' => array(
                $this->expected_artifact_42,
                $this->expected_artifact_66,
                $this->expected_artifact_9001,
            )
        ));
    }

    public function itReturnsNoMatchingResults() {
        stub($this->report)->getMatchingIds()->returns(array('id' => '', 'last_changeset_id' => ''));

        stub($this->report_factory)->getReportById()->returns($this->report);
        $soap_response = $this->server->getArtifactsFromReport($this->session_key, $this->report_id, 0, 10);
        $this->assertEqual($soap_response, array(
            'total_artifacts_number' => 0,
            'artifacts' => array()
        ));
    }
}
*/
class Tracker_SOAPServer_getFileFieldInfo_Test extends Tracker_SOAPServer_BaseTest {

    public function itRaisesAnExceptionIfTrackerIsNotReadable() {
        $artifact_id = 55;
        $artifact_in_unreadable_tracker = anArtifact()->withId($artifact_id)->withTracker($this->unreadable_tracker)->build();
        stub($this->artifact_factory)->getArtifactById($artifact_id)->returns($artifact_in_unreadable_tracker);
        $this->expectException('SoapFault');
        $this->server->getFileFieldInfo($this->session_key, $artifact_id, 0);
    }

    public function itRaisesAnExceptionIfFieldIsNotReadable() {
        $artifact_id = 55;
        $artifact    = anArtifact()->withId($artifact_id)->withTracker($this->tracker)->build();
        stub($this->artifact_factory)->getArtifactById($artifact_id)->returns($artifact);

        $field_id = 356;
        $field = aMockField()->build();
        stub($field)->userCanRead()->returns(false);
        stub($this->formelement_factory)->getFormElementById($field_id)->returns($field);

        $this->expectException('SoapFault');
        $this->server->getFileFieldInfo($this->session_key, $artifact_id, $field_id);
    }

    public function itRaisesAnExceptionIfFieldIsNotFile() {
        $artifact_id = 55;
        $artifact    = anArtifact()->withId($artifact_id)->withTracker($this->tracker)->build();
        stub($this->artifact_factory)->getArtifactById($artifact_id)->returns($artifact);

        $field_id = 356;
        $field = aMockField()->build();
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFormElementById($field_id)->returns($field);

        $this->expectException('SoapFault');
        $this->server->getFileFieldInfo($this->session_key, $artifact_id, $field_id);
    }

    /*public function itDoesStuffWhenThereAreNoErrors() {
        $artifact_id = 55;
        $artifact    = anArtifact()->withId($artifact_id)->withTracker($this->tracker)->build();
        stub($this->artifact_factory)->getArtifactById($artifact_id)->returns($artifact);

        $field_id = 356;
        $field = mock('Tracker_FormElement_Field_File');
        stub($field)->userCanRead()->returns(true);
        stub($this->formelement_factory)->getFormElementById($field_id)->returns($field);

        $this->server->getFileFieldInfo($this->session_key, $artifact_id, $field_id);
    }*/
}

?>