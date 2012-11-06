<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/tracker/ArtifactType.class.php';
require_once dirname(__FILE__).'/../../../plugins/tracker/include/constants.php';
require_once TRACKER_BASE_DIR.'/Tracker/TrackerManager.class.php';
require_once TRACKER_BASE_DIR.'/Tracker/Migration/V3.class.php';

abstract class MigrateDefaultBugTrackerTest extends TuleapDbTestCase {
    protected $admin_user_id = 1;
    protected $tracker_id   = 1;
    /** @var Tracker */
    protected $tracker;
    /** @var Tracker_FormElementFactory */
    protected $form_element_factory;

    private static $tracker_converted = false;
    
    public function __construct() {
        parent::__construct();

        // Uncomment this during development to avoid aweful 50" setUp
        $this->thisTestIsUnderDevelopment();
    }

    public function setUp() {
        parent::setUp();
        Config::store();
        Config::set('codendi_log', dirname(__FILE__));

        if (!self::$tracker_converted && $this->thisTestIsNotUnderDevelopment()) {
            $this->convertTracker();
        }
        
        $this->form_element_factory = Tracker_FormElementFactory::instance();
        $this->tracker_factory = TrackerFactory::instance();
        $this->tracker = $this->tracker_factory->getTrackerById($this->tracker_id);
    }

    public function tearDown() {
        if (is_file(Config::get('codendi_log').'/tv3_to_tv5.log')) {
            unlink(Config::get('codendi_log').'/tv3_to_tv5.log');
        }
        Config::restore();
        parent::tearDown();
    }

    protected function convertTracker() {
        $res = db_query('SELECT * FROM artifact_group_list WHERE item_name = "bug"');
        $row = db_fetch_array($res);

        $this->assertEqual($row['item_name'], 'bug');
        $trackerv3_id = $row['group_artifact_id'];
        $v3_migration = new Tracker_Migration_V3(TrackerFactory::instance());
        $project = ProjectManager::instance()->getProject(100);
        $name = 'Defect';
        $description = "defect tracker";
        $itemname = "defect";
        $tv3 = new ArtifactType($project, $trackerv3_id);


        $tracker = $v3_migration->createTV5FromTV3($project, $name, $description, $itemname, $tv3);
        $this->tracker_id = $tracker->getId();
        TrackerFactory::clearInstance();
        self::$tracker_converted = true;
    }

}

class MigrateTracker_TrackerConfigTest extends MigrateDefaultBugTrackerTest {

    public function itCreatedTrackerV5WithDefaultParameters() {
        $this->assertEqual($this->tracker->getName(), 'Defect');
        $this->assertEqual($this->tracker->getDescription(), 'defect tracker');
        $this->assertEqual($this->tracker->getItemName(), 'defect');
        $this->assertEqual($this->tracker->getGroupId(), 100);
    }

    public function itHasNoParent() {
        $this->assertNull($this->tracker->getParent());
    }

    public function itGivesFullAccessToAllUsers() {
        $this->assertEqual($this->tracker->getPermissions(), array(
            UGroup::ANONYMOUS => array(
                Tracker::PERMISSION_FULL
            )
        ));
    }

    public function itHasATitleSemantic() {
        $field = $this->tracker->getTitleField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_String');
        $this->assertEqual($field->getName(), "summary");
        $this->assertEqual($field->getLabel(), "Summary");
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissions(), array(
            UGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            UGroup::REGISTERED => array(
                Tracker_FormElement::PERMISSION_SUBMIT
            ),
            UGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasAStatusSemantic() {
        $field = $this->tracker->getStatusField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "status_id");
        $this->assertEqual($field->getLabel(), "Status");
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissions(), array(
            UGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            UGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }
    
    public function itHasOnlyOneOpenValueForStatusSemantic() {
        $semantic_status = Tracker_SemanticFactory::instance()->getSemanticStatusFactory()->getByTracker($this->tracker);
        $open_values     = $semantic_status->getOpenValues();
        $this->assertCount($open_values, 1);
        $open_value = $semantic_status->getField()->getListValueById($open_values[0]);
        $this->assertEqual($open_value->getLabel(), 'Open');
    }

    public function itHasAnAssignedToSemantic() {
        $field = $this->tracker->getContributorField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "assigned_to");
        $this->assertEqual($field->getLabel(), "Assigned to");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissions(), array(
            UGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            UGroup::REGISTERED => array(
                Tracker_FormElement::PERMISSION_SUBMIT
            ),
            UGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }
}

class MigrateTracker_TrackerFieldsTest extends MigrateDefaultBugTrackerTest {
    public function itHasSubmittedBy() {
        $field = $this->form_element_factory->getFormElementByName($this->tracker_id, 'submitted_by');
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "submitted_by");
        $this->assertEqual($field->getLabel(), "Submitted by");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissions(), array(
            UGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
        ));
    }

    public function itHasATextFieldDescription() {
        $field = $this->form_element_factory->getFormElementByName($this->tracker_id, 'details');
        $this->assertIsA($field, 'Tracker_FormElement_Field_Text');
        $this->assertEqual($field->getName(), "details");
        $this->assertEqual($field->getLabel(), "Original Submission");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissions(), array(
            UGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            UGroup::REGISTERED => array(
                Tracker_FormElement::PERMISSION_SUBMIT
            ),
            UGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasAnUnusedDateFieldCloseDate() {
        $field = $this->form_element_factory->getFormElementByName($this->tracker_id, 'close_date');
        $this->assertIsA($field, 'Tracker_FormElement_Field_Date');
        $this->assertEqual($field->getName(), "close_date");
        $this->assertEqual($field->getLabel(), "Close Date");
        $this->assertFalse($field->isRequired());
        $this->assertFalse($field->isUsed());
        $this->assertEqual($field->getPermissions(), array(
            UGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            UGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasAnUnusedField() {
        $field = $this->form_element_factory->getFormElementByName($this->tracker_id, 'originator_name');
        $this->assertIsA($field, 'Tracker_FormElement_Field_String');
        $this->assertEqual($field->getName(), "originator_name");
        $this->assertEqual($field->getLabel(), "Originator Name");
        $this->assertFalse($field->isUsed());
    }

    public function itHasAListFieldResolutionWithValues() {
        $field = $this->form_element_factory->getFormElementByName($this->tracker_id, 'resolution_id');
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "resolution_id");
        $this->assertEqual($field->getLabel(), "Resolution");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());

        $this->compareValuesToLabel($field->getAllValues(), array('Fixed', 'Invalid', 'Wont Fix', 'Later', 'Remind', 'Works for me', 'Duplicate'));
    }

    protected function compareValuesToLabel(array $values, array $labels) {
        $this->assertCount($values, count($labels));
        $i = 0;
        while($value = array_shift($values)) {
            $this->assertIsA($value, 'Tracker_FormElement_Field_List_Bind_StaticValue');
            $this->assertEqual($value->getLabel(), $labels[$i++]);
            $this->assertFalse($value->isHidden());
        }
    }
}

class MigrateTracker_TrackerReportsTest extends MigrateDefaultBugTrackerTest {
    /** @var Tracker_ReportFactory */
    private $report_factory;
    /** @var Tracker_Report */
    private $bugs_report;

    public function setUp() {
        parent::setUp();
        $this->report_factory = Tracker_ReportFactory::instance();
        $this->bugs_report    = $this->getReportByName('Bugs');
    }

    protected function getReportByName($name) {
        foreach ($this->report_factory->getReportsByTrackerId($this->tracker_id, null) as $report) {
            if ($report->name == $name) {
                return $report;
            }
        }
    }

    public function itHasTwoReports() {
        $this->assertCount($this->report_factory->getReportsByTrackerId($this->tracker_id, null), 2);
    }


    public function itHasAReportNamedBugs() {
        $this->assertEqual($this->bugs_report->name, 'Bugs');
    }

    public function itHasFourCriteria() {
        $criteria = $this->bugs_report->getCriteria();
        $this->thereAreCriteriaForFields($criteria, array('Category', 'Group', 'Assigned to', 'Status'));
    }

    protected function thereAreCriteriaForFields(array $criteria, array $field_labels) {
        $this->assertCount($criteria, count($field_labels));
        foreach ($field_labels as $label) {
            $this->assertTrue($this->criteriaContainOneCriterionForField($criteria, $label));
        }
    }

    protected function criteriaContainOneCriterionForField(array $criteria, $field_label) {
        foreach ($criteria as $criterion) {
            if ($criterion->field->getLabel() == $field_label) {
                return true;
            }
        }
        return false;
    }

    public function itHasATableRenderer() {
        $renderers = $this->bugs_report->getRenderers();
        $this->assertCount($renderers, 1);

        $renderer = array_shift($renderers);
        $this->assertIsA($renderer, 'Tracker_Report_Renderer_Table');

        $columns = $renderer->getTableColumns(false, true, false);
        $this->thereAreColumnsForFields($columns, array('Submitted by', 'Submitted on', 'Artifact ID', 'Summary', 'Assigned to'));
    }

    public function thereAreColumnsForFields($columns, $field_labels) {
        $this->assertCount($columns, count($field_labels));
        foreach ($field_labels as $label) {
            $this->assertTrue($this->columnsContainOneColumnForField($columns, $label));
        }
    }

    public function columnsContainOneColumnForField($columns, $field_label) {
        foreach ($columns as $column) {
            if ($column['field']->getLabel() == $field_label) {
                return true;
            }
        }
        return false;
    }

}

?>
