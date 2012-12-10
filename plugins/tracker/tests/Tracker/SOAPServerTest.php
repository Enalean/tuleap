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

    public function setUp() {
        parent::setUp();

        $current_user        = mock('User');
        stub($current_user)->isSuperUser()->returns(true);
        stub($current_user)->isLoggedIn()->returns(true);
        $user_manager        = stub('UserManager')->getCurrentUser($this->session_key)->returns($current_user);
        
        $project             = mock('Project');
        stub($project)->usesService()->returns(true);
        
        $project_manager     = mock('ProjectManager');
        stub($project_manager)->getGroupByIdForSoap()->returns($project);
        
        $permissions_manager = mock('PermissionsManager');

        $artifact_factory    = mock('Tracker_ArtifactFactory');
        $this->setUpArtifacts($artifact_factory);

        $dao = mock('Tracker_ReportDao');
        $this->setUpArtifactResults($dao);

        $formelement_factory = mock('Tracker_FormElementFactory');
        $this->setUpFields($formelement_factory);

        $tracker_factory = mock('TrackerFactory');
        $this->setUpTrackers($tracker_factory);

        $soap_user_manager = mock('SOAP_UserManager');
        stub($soap_user_manager)->continueSession($this->session_key)->returns($current_user);

        $this->server = new Tracker_SOAPServer (
                    $soap_user_manager,
                    $project_manager,
                    $tracker_factory,
                    $permissions_manager,
                    $dao,
                    $formelement_factory,
                    $artifact_factory 
        );
    }

    private function setUpArtifacts(Tracker_ArtifactFactory $artifact_factory) {
        $changesets_empty = array(stub('Tracker_Artifact_Changeset')->getValues()->returns(array()));
        $changesets       = stub('Tracker_Artifact_Changeset')->getValues()->returns(array("title" => "title"));
        $artifact_42      = anArtifact()->withId(42)->withTrackerId($this->tracker_id)->withChangesets($changesets_empty)->build();
        $artifact_66      = anArtifact()->withId(66)->withTrackerId($this->tracker_id)->withChangesets($changesets_empty)->build();
        $artifact_9001    = anArtifact()->withId(9001)->withTrackerId($this->tracker_id)->withChangesets($changesets_empty)->build();
        
        $artifact_9999    = mock('Tracker_Artifact');
        stub($artifact_9999)->getId()->returns(9999);
        stub($artifact_9999)->getTrackerId()->returns($this->tracker_id);
        stub($artifact_9999)->getLastChangeset()->returns($changesets);
        stub($artifact_9999)->validateNewChangeset()->returns(true);

        stub($artifact_factory)->getArtifactById(42)->returns($artifact_42);
        stub($artifact_factory)->getArtifactById(66)->returns($artifact_66);
        stub($artifact_factory)->getArtifactById(9001)->returns($artifact_9001);
        stub($artifact_factory)->getArtifactById(9999)->returns($artifact_9999);
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

        $field_title = mock('Tracker_FormElement_Field_Text');
        stub($field_title)->getFieldData()->returns('titre');

        stub($formelement_factory)->getUsedFieldByName()->returns($field_title);
    }

    private function setUpTrackers(TrackerFactory $tracker_factory) {
        $tracker      = aMockTracker()->withId($this->tracker_id)->build();
        $unreadable_tracker = aMockTracker()->withId($this->unreadable_tracker_id)->build();
        stub($tracker)->userCanView()->returns(true);
        stub($tracker)->userIsAdmin()->returns(true);
        stub($unreadable_tracker)->userCanView()->returns(false);
        stub($tracker_factory)->getTrackerById($this->tracker_id)->returns($tracker);
        stub($tracker_factory)->getTrackerById($this->unreadable_tracker_id)->returns($unreadable_tracker);
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
    
    public function itReturnsTheArtifactIDWhenThereIsNoChangeOnUpdate() {
        $title_update   = $this->convertCriteriaToSoapParameter(array(
            array(
                'field_name'  => 'title',
                'field_label' => 'title',
                'field_value' => 'titre',
            )
        ));
        $comment        = NULL;
        $comment_format = NULL;
        
        $results = $this->server->updateArtifact($this->session_key, null, $this->tracker_id, 9999, $title_update, $comment, $comment_format);
        var_dump($results);
        $this->assertEqual($results, 9999);
    }
}
?>