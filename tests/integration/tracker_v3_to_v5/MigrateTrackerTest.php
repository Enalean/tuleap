<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
require_once __DIR__ . '/../../../plugins/tracker/include/trackerPlugin.class.php';

abstract class MigrateDefaultTrackersTest extends TuleapDbTestCase {
    private static $defect_tracker_converted = false;

    protected $admin_user_id      = 1;
    protected static $defect_tracker_id  = 1;
    protected static $task_tracker_id = 2;


    /** @var Tracker */
    protected $defect_tracker;
    /** @var Tracker */
    protected $task_tracker;

    /** @var Tracker_FormElementFactory */
    protected $form_element_factory;
    /** @var Tracker_Factory */
    protected $tracker_factory;


    public function __construct() {
        parent::__construct();

        // Uncomment this during development to avoid aweful 50" setUp
        //$this->markThisTestUnderDevelopment();
    }

    public function setUp() {
        parent::setUp();
        $sys_dbhost   = ForgeConfig::get('sys_dbhost');
        $sys_dbuser   = ForgeConfig::get('sys_dbuser');
        $sys_dbpasswd = ForgeConfig::get('sys_dbpasswd');
        $sys_dbname   = ForgeConfig::get('sys_dbname');
        ForgeConfig::store();
        ForgeConfig::set('codendi_log', $this->getTmpDir());
        ForgeConfig::set('sys_dbhost', $sys_dbhost);
        ForgeConfig::set('sys_dbuser', $sys_dbuser);
        ForgeConfig::set('sys_dbpasswd', $sys_dbpasswd);
        ForgeConfig::set('sys_dbname', $sys_dbname);

        if (!self::$defect_tracker_converted && $this->thisTestIsNotUnderDevelopment()) {
            $this->convertTrackers();
        }

        $this->form_element_factory = Tracker_FormElementFactory::instance();
        $this->tracker_factory      = TrackerFactory::instance();

        $this->defect_tracker = $this->tracker_factory->getTrackerById(self::$defect_tracker_id);
        $this->task_tracker = $this->tracker_factory->getTrackerById(self::$task_tracker_id);
    }

    public function tearDown() {
        ForgeConfig::restore();
        parent::tearDown();
    }

    protected function convertTrackers() {
        $this->convertBugTracker();
        $this->convertTaskTracker();
        TrackerFactory::clearInstance();
        self::$defect_tracker_converted = true;
    }

    protected function convertBugTracker() {
        $res = db_query('SELECT * FROM artifact_group_list WHERE item_name = "bug" AND group_id = 100');
        if (db_numrows($res) !== 1) {
            die('No Tracker v3 data. Migration impossible');
        }
        $row = db_fetch_array($res);


        $defect_trackerv3_id = $row['group_artifact_id'];
        $v3_migration = new Tracker_Migration_V3(TrackerFactory::instance());
        $project = ProjectManager::instance()->getProject(100);
        $name = 'Defect';
        $description = "defect tracker";
        $itemname = "defect";
        $tv3 = new ArtifactType($project, $defect_trackerv3_id);

        $defect_tracker = $v3_migration->createTV5FromTV3($project, $name, $description, $itemname, $tv3);
        self::$defect_tracker_id = $defect_tracker->getId();
    }

    protected function convertTaskTracker() {
        $res = db_query('SELECT * FROM artifact_group_list WHERE item_name = "task" AND group_id = 100');
        $row = db_fetch_array($res);

        $trackerv3_id = $row['group_artifact_id'];
        $v3_migration = new Tracker_Migration_V3(TrackerFactory::instance());
        $project = ProjectManager::instance()->getProject(100);
        $name = 'Tasks';
        $description = "tasks tracker";
        $itemname = "tsk";
        $tv3 = new ArtifactType($project, $trackerv3_id);

        $task_tracker = $v3_migration->createTV5FromTV3($project, $name, $description, $itemname, $tv3);
        self::$task_tracker_id = $task_tracker->getId();
    }
}

class MigrateTracker_DefectTrackerConfigTest extends MigrateDefaultTrackersTest {

    public function itCreatedTrackerV5WithDefaultParameters() {
        $this->assertEqual($this->defect_tracker->getName(), 'Defect');
        $this->assertEqual($this->defect_tracker->getDescription(), 'defect tracker');
        $this->assertEqual($this->defect_tracker->getItemName(), 'defect');
        $this->assertEqual($this->defect_tracker->getGroupId(), 100);
    }

    public function itHasNoParent() {
        $this->assertNull($this->defect_tracker->getParent());
    }

    public function itGivesFullAccessToAllUsers() {
        $this->assertEqual($this->defect_tracker->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker::PERMISSION_FULL
            )
        ));
    }

    public function itHasATitleSemantic() {
        $field = $this->defect_tracker->getTitleField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_String');
        $this->assertEqual($field->getName(), "summary");
        $this->assertEqual($field->getLabel(), "Summary");
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::REGISTERED => array(
                Tracker_FormElement::PERMISSION_SUBMIT
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasAStatusSemantic() {
        $field = $this->defect_tracker->getStatusField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "status_id");
        $this->assertEqual($field->getLabel(), "Status");
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasOnlyOneOpenValueForStatusSemantic() {
        $semantic_status = Tracker_SemanticFactory::instance()->getSemanticStatusFactory()->getByTracker($this->defect_tracker);
        $open_values     = $semantic_status->getOpenValues();
        $this->assertCount($open_values, 1);
        $open_value = $semantic_status->getField()->getListValueById($open_values[0]);
        $this->assertEqual($open_value->getLabel(), 'Open');
    }

    public function itHasAnAssignedToSemantic() {
        $field = $this->defect_tracker->getContributorField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "assigned_to");
        $this->assertEqual($field->getLabel(), "Assigned to");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertFalse($field->isMultiple());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::REGISTERED => array(
                Tracker_FormElement::PERMISSION_SUBMIT
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }
}

class MigrateTracker_DefectTrackerFieldsTest extends MigrateDefaultTrackersTest {
    public function itHasSubmittedBy() {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'submitted_by');
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "submitted_by");
        $this->assertEqual($field->getLabel(), "Submitted by");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
        ));
    }

    public function itHasATextFieldDescription() {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'details');
        $this->assertIsA($field, 'Tracker_FormElement_Field_Text');
        $this->assertEqual($field->getName(), "details");
        $this->assertEqual($field->getLabel(), "Original Submission");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::REGISTERED => array(
                Tracker_FormElement::PERMISSION_SUBMIT
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasAnUnusedDateFieldCloseDate() {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'close_date');
        $this->assertIsA($field, 'Tracker_FormElement_Field_Date');
        $this->assertEqual($field->getName(), "close_date");
        $this->assertEqual($field->getLabel(), "Close Date");
        $this->assertFalse($field->isRequired());
        $this->assertFalse($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasAnUnusedField() {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'originator_name');
        $this->assertIsA($field, 'Tracker_FormElement_Field_String');
        $this->assertEqual($field->getName(), "originator_name");
        $this->assertEqual($field->getLabel(), "Originator Name");
        $this->assertFalse($field->isUsed());
    }

    public function itHasAListFieldResolutionWithValues() {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'resolution_id');
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

class MigrateTracker_DefectTrackerReportsTest extends MigrateDefaultTrackersTest {
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
        foreach ($this->report_factory->getReportsByTrackerId(self::$defect_tracker_id, null) as $report) {
            if ($report->name == $name) {
                return $report;
            }
        }
    }

    public function itHasTwoReports() {
        $this->assertCount($this->report_factory->getReportsByTrackerId(self::$defect_tracker_id, null), 2);
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

class MigrateTracker_TaskTrackerConfigTest extends MigrateDefaultTrackersTest {

    public function itCreatedTrackerV5WithDefaultParameters() {
        $this->assertEqual($this->task_tracker->getName(), 'Tasks');
        $this->assertEqual($this->task_tracker->getDescription(), 'tasks tracker');
        $this->assertEqual($this->task_tracker->getItemName(), 'tsk');
        $this->assertEqual($this->task_tracker->getGroupId(), 100);
    }

    public function itHasNoParent() {
        $this->assertNull($this->task_tracker->getParent());
    }

    public function itGivesFullAccessToAllUsers() {
        $this->assertEqual($this->task_tracker->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker::PERMISSION_FULL
            )
        ));
    }

    public function itHasATitleSemantic() {
        $field = $this->task_tracker->getTitleField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_String');
        $this->assertEqual($field->getName(), "summary");
        $this->assertEqual($field->getLabel(), "Summary");
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_SUBMIT,
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasAStatusSemantic() {
        $field = $this->task_tracker->getStatusField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "status_id");
        $this->assertEqual($field->getLabel(), "Status");
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasOnlyOneOpenValueForStatusSemantic() {
        $semantic_status = Tracker_SemanticFactory::instance()->getSemanticStatusFactory()->getByTracker($this->task_tracker);
        $open_values     = $semantic_status->getOpenValues();
        $this->assertCount($open_values, 1);
        $open_value = $semantic_status->getField()->getListValueById($open_values[0]);
        $this->assertEqual($open_value->getLabel(), 'Open');
    }

    public function itHasAnAssignedToSemantic() {
        $field = $this->task_tracker->getContributorField();
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "multi_assigned_to");
        $this->assertEqual($field->getLabel(), "Assigned to (multiple)");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertTrue($field->isMultiple());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_SUBMIT,
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }
}

class MigrateTracker_TaskTrackerFieldsTest extends MigrateDefaultTrackersTest {

    public function itHasSubmittedBy() {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'submitted_by');
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "submitted_by");
        $this->assertEqual($field->getLabel(), "Submitted by");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
        ));
    }

    public function itHasATextFieldDescription() {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'details');
        $this->assertIsA($field, 'Tracker_FormElement_Field_Text');
        $this->assertEqual($field->getName(), "details");
        $this->assertEqual($field->getLabel(), "Original Submission");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_SUBMIT,
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasADateFieldStartDate() {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        $this->assertIsA($field, 'Tracker_FormElement_Field_Date');
        $this->assertEqual($field->getName(), "start_date");
        $this->assertEqual($field->getLabel(), "Start Date");
        $this->assertFalse($field->isRequired());
        $this->assertTrue($field->isUsed());
        $this->assertEqual($field->getPermissionsByUgroupId(), array(
            ProjectUGroup::ANONYMOUS => array(
                Tracker_FormElement::PERMISSION_READ
            ),
            ProjectUGroup::PROJECT_MEMBERS => array(
                Tracker_FormElement::PERMISSION_SUBMIT,
                Tracker_FormElement::PERMISSION_UPDATE
            ),
        ));
    }

    public function itHasAnUnusedField() {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'stage');
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "stage");
        $this->assertEqual($field->getLabel(), "Stage");
        $this->assertFalse($field->isUsed());
    }

    public function itHasAListFieldResolutionWithValues() {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'severity');
        $this->assertIsA($field, 'Tracker_FormElement_Field_List');
        $this->assertEqual($field->getName(), "severity");
        $this->assertEqual($field->getLabel(), "Priority");
        $this->assertTrue($field->isRequired());
        $this->assertTrue($field->isUsed());

        $this->compareValuesToLabel($field->getAllValues(), array('1 - Lowest', '2', '3', '4', '5 - Medium', '6', '7', '8', '9 - Highest'));
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

class MigrateTracker_TaskTrackerReportsTest extends MigrateDefaultTrackersTest {
    /** @var Tracker_ReportFactory */
    private $report_factory;
    /** @var Tracker_Report */
    private $tasks_report;

    public function setUp() {
        parent::setUp();
        $this->report_factory = Tracker_ReportFactory::instance();
        $this->tasks_report    = $this->getReportByName('Tasks');
    }

    protected function getReportByName($name) {
        foreach ($this->report_factory->getReportsByTrackerId(self::$task_tracker_id, null) as $report) {
            if ($report->name == $name) {
                return $report;
            }
        }
    }

    public function itHasTwoReports() {
        $this->assertCount($this->report_factory->getReportsByTrackerId(self::$task_tracker_id, null), 2);
    }


    public function itHasAReportNamedBugs() {
        $this->assertEqual($this->tasks_report->name, 'Tasks');
    }

    public function itHasThreeCriteria() {
        $criteria = $this->tasks_report->getCriteria();
        $this->thereAreCriteriaForFields($criteria, array('Subproject', 'Assigned to (multiple)', 'Status'));
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
        $renderers = $this->tasks_report->getRenderers();
        $this->assertCount($renderers, 1);

        $renderer = array_shift($renderers);
        $this->assertIsA($renderer, 'Tracker_Report_Renderer_Table');

        $columns = $renderer->getTableColumns(false, true, false);
        $this->thereAreColumnsForFields($columns, array('Artifact ID', 'Assigned to (multiple)', 'Subproject', 'Effort', 'Status', 'Start Date', 'Summary'));
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

class MigrateTracker_TaskTrackerDateReminder_startDateTest extends MigrateDefaultTrackersTest {

    private $notified_ugroups = array(ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::TRACKER_ADMIN);

    public function setUp() {
        parent::setUp();
        $this->start_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');

        $factory = new Tracker_DateReminderFactory($this->task_tracker, new Tracker_DateReminderRenderer($this->task_tracker));
        $this->reminders = $factory->getTrackerReminders();
    }

    public function itSendsAnEmailToProjectAndTrackerAdminsTwoDaysBeforeStartDate() {
        $this->assertEqual($this->reminders[0]->getDistance(), 2);
        $this->assertEqual($this->reminders[0]->getNotificationType(), Tracker_DateReminder::BEFORE);
        $this->assertEqual($this->reminders[0]->getField(), $this->start_date_field);
        $this->assertEqual($this->reminders[0]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEqual($this->reminders[0]->getUgroups(true), $this->notified_ugroups);
        $this->assertEqual($this->reminders[0]->getRoles(), NULL);
    }

    public function itSendsASecondEmailOnStartDate() {
        $this->assertEqual($this->reminders[1]->getDistance(), 0);
        $this->assertEqual($this->reminders[1]->getNotificationType(), Tracker_DateReminder::BEFORE);
        $this->assertEqual($this->reminders[1]->getField(), $this->start_date_field);
        $this->assertEqual($this->reminders[1]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEqual($this->reminders[1]->getUgroups(true), $this->notified_ugroups);
        $this->assertEqual($this->reminders[1]->getRoles(), NULL);
    }

    public function itSendsTheLastEmailTwoDaysAfterStartDate() {
        $this->assertEqual($this->reminders[2]->getDistance(), 2);
        $this->assertEqual($this->reminders[2]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEqual($this->reminders[2]->getField(), $this->start_date_field);
        $this->assertEqual($this->reminders[2]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEqual($this->reminders[2]->getUgroups(true), $this->notified_ugroups);
        $this->assertEqual($this->reminders[2]->getRoles(), NULL);
    }
}

class MigrateTracker_TaskTrackerDateReminder_endDateTest extends MigrateDefaultTrackersTest {

    private $notified_ugroups = array(ProjectUGroup::PROJECT_MEMBERS);
    private $notified_roles   = array();


    public function setUp() {
        parent::setUp();
        $this->end_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'end_date');
        $submitterRole = new Tracker_DateReminder_Role_Submitter();
        $this->notified_roles = array($submitterRole);

        $factory = new Tracker_DateReminderFactory($this->task_tracker, new Tracker_DateReminderRenderer($this->task_tracker));
        $this->reminders = $factory->getTrackerReminders();
    }

    public function itSendsAnEmailToProjectMembersAndSubmitterOneDayAfterEndDate() {
        $this->assertEqual($this->reminders[3]->getDistance(), 1);
        $this->assertEqual($this->reminders[3]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEqual($this->reminders[3]->getField(), $this->end_date_field);
        $this->assertEqual($this->reminders[3]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEqual($this->reminders[3]->getUgroups(true), $this->notified_ugroups);
        $this->assertEqual($this->reminders[3]->getRoles(), $this->notified_roles);
    }

    public function itSendsAnEmailToProjectMembersAndSubmitterThreeDaysAfterEndDate() {
        $this->assertEqual($this->reminders[4]->getDistance(), 3);
        $this->assertEqual($this->reminders[4]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEqual($this->reminders[4]->getField(), $this->end_date_field);
        $this->assertEqual($this->reminders[4]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEqual($this->reminders[4]->getUgroups(true), $this->notified_ugroups);
        $this->assertEqual($this->reminders[4]->getRoles(), $this->notified_roles);
    }


}

class MigrateTracker_TaskTrackerDateReminder_dueDateTest extends MigrateDefaultTrackersTest {

    private $notified_roles = array();

    public function setUp() {
        parent::setUp();
        $this->due_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'due_date');
        $submitterRole = new Tracker_DateReminder_Role_Submitter();
        $this->notified_roles = array($submitterRole);

        $factory = new Tracker_DateReminderFactory($this->task_tracker, new Tracker_DateReminderRenderer($this->task_tracker));
        $this->reminders = $factory->getTrackerReminders();
    }

    public function itSendsAnEmailToSubmitterOneDaysAfterDueDate() {
        $this->assertEqual($this->reminders[5]->getDistance(), 1);
        $this->assertEqual($this->reminders[5]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEqual($this->reminders[5]->getField(), $this->due_date_field);
        $this->assertEqual($this->reminders[5]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEqual($this->reminders[5]->getUgroups(true), array(""));
        $this->assertEqual($this->reminders[5]->getRoles(), $this->notified_roles);
    }

    public function itSendsASecondEmailThreeDaysAfterDueDate() {
        $this->assertEqual($this->reminders[6]->getDistance(), 3);
        $this->assertEqual($this->reminders[6]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEqual($this->reminders[6]->getField(), $this->due_date_field);
        $this->assertEqual($this->reminders[6]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEqual($this->reminders[6]->getUgroups(true), array(""));
        $this->assertEqual($this->reminders[6]->getRoles(), $this->notified_roles);
    }

    public function itCreateReminderWhenTheListOfUgroupsIsEmptyButNotTheTrackerRoles() {
        $this->assertCount($this->reminders, 7);
    }

}
?>
