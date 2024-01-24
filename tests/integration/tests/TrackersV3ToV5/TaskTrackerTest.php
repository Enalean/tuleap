<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tests\Integration\TrackersV3ToV5;

use ArtifactType;
use PermissionsManager;
use ProjectManager;
use ProjectUGroup;
use Tracker;
use Tracker_DateReminder;
use Tracker_DateReminder_Role_Submitter;
use Tracker_DateReminderFactory;
use Tracker_DateReminderRenderer;
use Tracker_FormElement;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;
use Tracker_FormElementFactory;
use Tracker_Migration_V3;
use Tracker_Report_Renderer_Table;
use Tracker_ReportFactory;
use Tracker_SemanticFactory;
use TrackerFactory;
use Tuleap\DB\DBFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\DateReminder\DateReminderDao;

class TaskTrackerTest extends TestIntegrationTestCase
{
    use GlobalLanguageMock;

    private static $backup_codendi_log;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var int
     */
    private static $task_tracker_id;
    /**
     * @var \Tracker
     */
    private $task_tracker;
    /**
     * @var Tracker_ReportFactory
     */
    private $report_factory;
    /**
     * @var \Tracker_Report
     */
    private $tasks_report;

    /**
     * @beforeClass
     */
    public static function convertTaskTracker()
    {
        self::$backup_codendi_log = \ForgeConfig::get('backup_codendi_log');
        \ForgeConfig::set('codendi_log', '/tmp');

        $db      = DBFactory::getMainTuleapDBConnection()->getDB();
        $results = $db->run('SELECT * FROM artifact_group_list WHERE item_name = "task" AND group_id = 100');
        if (count($results) !== 1) {
            throw new \RuntimeException('No Tracker v3 data. Migration impossible');
        }
        $row = $results[0];

        $trackerv3_id = $row['group_artifact_id'];
        $v3_migration = new Tracker_Migration_V3(TrackerFactory::instance());
        $project      = ProjectManager::instance()->getProject(100);
        $name         = 'Tasks';
        $description  = "tasks tracker";
        $itemname     = "tsk";
        $tv3          = new ArtifactType($project, $trackerv3_id);

        $task_tracker          = $v3_migration->createTV5FromTV3($project, $name, $description, $itemname, $tv3);
        self::$task_tracker_id = $task_tracker->getId();
        unset($GLOBALS['Language']);
    }

    /**
     * @afterClass
     */
    public static function resetForgeConfig()
    {
        \ForgeConfig::set('codendi_log', self::$backup_codendi_log);
    }

    protected function setUp(): void
    {
        PermissionsManager::clearInstance();
        $this->form_element_factory = Tracker_FormElementFactory::instance();
        $this->tracker_factory      = TrackerFactory::instance();

        $this->task_tracker = $this->tracker_factory->getTrackerById(self::$task_tracker_id);

        $this->report_factory = Tracker_ReportFactory::instance();
        $this->tasks_report   = $this->getReportByName('Tasks');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    protected function getReportByName($name)
    {
        foreach ($this->report_factory->getReportsByTrackerId(self::$task_tracker_id, null) as $report) {
            if ($report->name === $name) {
                return $report;
            }
        }
    }

    public function testItCreatedTrackerV5WithDefaultParameters()
    {
        $this->assertEquals($this->task_tracker->getName(), 'Tasks');
        $this->assertEquals($this->task_tracker->getDescription(), 'tasks tracker');
        $this->assertEquals($this->task_tracker->getItemName(), 'tsk');
        $this->assertEquals($this->task_tracker->getGroupId(), 100);
    }

    public function testItHasNoParent()
    {
        $this->assertNull($this->task_tracker->getParent());
    }

    public function testItGivesFullAccessToAllUsers()
    {
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [Tracker::PERMISSION_FULL,],
        ], $this->task_tracker->getPermissionsByUgroupId());
    }

    public function testItHasATitleSemantic()
    {
        $field = $this->task_tracker->getTitleField();
        $this->assertInstanceOf(Tracker_FormElement_Field_String::class, $field);
        $this->assertEquals($field->getName(), "summary");
        $this->assertEquals($field->getLabel(), "Summary");
        $this->assertEquals(1, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_SUBMIT,
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    public function testItHasAStatusSemantic()
    {
        $field = $this->task_tracker->getStatusField();
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "status_id");
        $this->assertEquals($field->getLabel(), "Status");
        $this->assertEquals(1, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    public function testItHasOnlyOneOpenValueForStatusSemantic()
    {
        $semantic_status = Tracker_SemanticFactory::instance()->getSemanticStatusFactory()->getByTracker($this->task_tracker);
        $open_values     = $semantic_status->getOpenValues();
        $this->assertCount(1, $open_values);
        $open_value = $semantic_status->getField()->getListValueById($open_values[0]);
        $this->assertEquals($open_value->getLabel(), 'Open');
    }

    public function testItHasAnAssignedToSemantic()
    {
        $field = $this->task_tracker->getContributorField();
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "multi_assigned_to");
        $this->assertEquals($field->getLabel(), "Assigned to (multiple)");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        $this->assertEquals(1, $field->isMultiple());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_SUBMIT,
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    public function testItHasSubmittedBy()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'submitted_by');
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "submitted_by");
        $this->assertEquals($field->getLabel(), "Submitted by");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    public function testItHasATextFieldDescription()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'details');
        $this->assertInstanceOf(Tracker_FormElement_Field_Text::class, $field);
        $this->assertEquals($field->getName(), "details");
        $this->assertEquals($field->getLabel(), "Original Submission");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_SUBMIT,
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    public function testItHasADateFieldStartDate()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        $this->assertInstanceOf(Tracker_FormElement_Field_Date::class, $field);
        $this->assertEquals($field->getName(), "start_date");
        $this->assertEquals($field->getLabel(), "Start Date");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_SUBMIT,
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    public function testItHasAnUnusedField()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'stage');
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "stage");
        $this->assertEquals($field->getLabel(), "Stage");
        $this->assertEquals(0, $field->isUsed());
    }

    public function testItHasAListFieldResolutionWithValues()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'severity');
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "severity");
        $this->assertEquals($field->getLabel(), "Priority");
        $this->assertEquals(1, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());

        $this->compareValuesToLabel($field->getAllValues(), ['1 - Lowest', '2', '3', '4', '5 - Medium', '6', '7', '8', '9 - Highest']);
    }

    protected function compareValuesToLabel(array $values, array $labels)
    {
        $this->assertCount(count($labels), $values);
        $i = 0;
        while ($value = array_shift($values)) {
            $this->assertInstanceOf(Tracker_FormElement_Field_List_Bind_StaticValue::class, $value);
            $this->assertEquals($value->getLabel(), $labels[$i++]);
            $this->assertEquals(0, $value->isHidden());
        }
    }

    public function testItHasTwoReports()
    {
        $this->assertCount(2, $this->report_factory->getReportsByTrackerId(self::$task_tracker_id, null));
    }

    public function testItHasAReportNamedBugs()
    {
        $this->assertEquals($this->tasks_report->name, 'Tasks');
    }

    public function testItHasThreeCriteria()
    {
        $criteria = $this->tasks_report->getCriteria();
        $this->thereAreCriteriaForFields($criteria, ['Subproject', 'Assigned to (multiple)', 'Status']);
    }

    protected function thereAreCriteriaForFields(array $criteria, array $field_labels)
    {
        $this->assertCount(count($field_labels), $criteria);
        foreach ($field_labels as $label) {
            $this->assertTrue($this->criteriaContainOneCriterionForField($criteria, $label));
        }
    }

    protected function criteriaContainOneCriterionForField(array $criteria, $field_label)
    {
        foreach ($criteria as $criterion) {
            if ($criterion->field->getLabel() == $field_label) {
                return true;
            }
        }
        return false;
    }

    public function testItHasATableRenderer()
    {
        $renderers = $this->tasks_report->getRenderers();
        $this->assertCount(1, $renderers);

        $renderer = array_shift($renderers);
        $this->assertInstanceOf(Tracker_Report_Renderer_Table::class, $renderer);

        $columns = $renderer->getTableColumns(false, true, false);
        $this->thereAreColumnsForFields($columns, ['Artifact ID', 'Assigned to (multiple)', 'Subproject', 'Effort', 'Status', 'Start Date', 'Summary']);
    }

    public function thereAreColumnsForFields($columns, $field_labels)
    {
        $this->assertCount(count($field_labels), $columns);
        foreach ($field_labels as $label) {
            $this->assertEquals(1, $this->columnsContainOneColumnForField($columns, $label));
        }
    }

    public function columnsContainOneColumnForField($columns, $field_label)
    {
        foreach ($columns as $column) {
            if ($column['field']->getLabel() == $field_label) {
                return true;
            }
        }
        return false;
    }

    public function testItSendsAnEmailToProjectAndTrackerAdminsTwoDaysBeforeStartDate()
    {
        $start_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        $factory          = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders        = $factory->getTrackerReminders();

        $this->assertEquals($reminders[0]->getDistance(), 2);
        $this->assertEquals($reminders[0]->getNotificationType(), Tracker_DateReminder::BEFORE);
        $this->assertEquals($reminders[0]->getField(), $start_date_field);
        $this->assertEquals($reminders[0]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEquals($reminders[0]->getUgroups(true), [ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::TRACKER_ADMIN]);
        $this->assertEquals($reminders[0]->getRoles(), []);
    }

    public function testItSendsASecondEmailOnStartDate()
    {
        $start_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        $factory          = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders        = $factory->getTrackerReminders();

        $this->assertEquals($reminders[1]->getDistance(), 0);
        $this->assertEquals($reminders[1]->getNotificationType(), Tracker_DateReminder::BEFORE);
        $this->assertEquals($reminders[1]->getField(), $start_date_field);
        $this->assertEquals($reminders[1]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEquals($reminders[1]->getUgroups(true), [ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::TRACKER_ADMIN]);
        $this->assertEquals($reminders[1]->getRoles(), []);
    }

    public function testItSendsTheLastEmailTwoDaysAfterStartDate()
    {
        $start_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        $factory          = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders        = $factory->getTrackerReminders();

        $this->assertEquals($reminders[2]->getDistance(), 2);
        $this->assertEquals($reminders[2]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEquals($reminders[2]->getField(), $start_date_field);
        $this->assertEquals($reminders[2]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEquals($reminders[2]->getUgroups(true), [ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::TRACKER_ADMIN]);
        $this->assertEquals($reminders[2]->getRoles(), []);
    }

    public function testItSendsAnEmailToProjectMembersAndSubmitterOneDayAfterEndDate()
    {
        $end_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'end_date');
        $submitterRole  = new Tracker_DateReminder_Role_Submitter();
        $notified_roles = [$submitterRole];
        $factory        = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders      = $factory->getTrackerReminders();

        $this->assertEquals($reminders[3]->getDistance(), 1);
        $this->assertEquals($reminders[3]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEquals($reminders[3]->getField(), $end_date_field);
        $this->assertEquals($reminders[3]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEquals($reminders[3]->getUgroups(true), [ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertEquals($reminders[3]->getRoles(), $notified_roles);
    }

    public function testItSendsAnEmailToProjectMembersAndSubmitterThreeDaysAfterEndDate()
    {
        $end_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'end_date');
        $submitterRole  = new Tracker_DateReminder_Role_Submitter();
        $notified_roles = [$submitterRole];
        $factory        = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders      = $factory->getTrackerReminders();

        $this->assertEquals($reminders[4]->getDistance(), 3);
        $this->assertEquals($reminders[4]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEquals($reminders[4]->getField(), $end_date_field);
        $this->assertEquals($reminders[4]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEquals($reminders[4]->getUgroups(true), [ProjectUGroup::PROJECT_MEMBERS]);
        $this->assertEquals($reminders[4]->getRoles(), $notified_roles);
    }

    public function testItSendsAnEmailToSubmitterOneDaysAfterDueDate()
    {
        $due_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'due_date');
        $submitterRole  = new Tracker_DateReminder_Role_Submitter();
        $notified_roles = [$submitterRole];

        $factory   = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders = $factory->getTrackerReminders();

        $this->assertEquals($reminders[5]->getDistance(), 1);
        $this->assertEquals($reminders[5]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEquals($reminders[5]->getField(), $due_date_field);
        $this->assertEquals($reminders[5]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEquals($reminders[5]->getUgroups(true), [""]);
        $this->assertEquals($reminders[5]->getRoles(), $notified_roles);
    }

    public function testItSendsASecondEmailThreeDaysAfterDueDate()
    {
        $due_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'due_date');
        $submitterRole  = new Tracker_DateReminder_Role_Submitter();
        $notified_roles = [$submitterRole];

        $factory   = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders = $factory->getTrackerReminders();

        $this->assertEquals($reminders[6]->getDistance(), 3);
        $this->assertEquals($reminders[6]->getNotificationType(), Tracker_DateReminder::AFTER);
        $this->assertEquals($reminders[6]->getField(), $due_date_field);
        $this->assertEquals($reminders[6]->getStatus(), Tracker_DateReminder::ENABLED);
        $this->assertEquals($reminders[6]->getUgroups(true), [""]);
        $this->assertEquals($reminders[6]->getRoles(), $notified_roles);
    }

    public function testItCreateReminderWhenTheListOfUgroupsIsEmptyButNotTheTrackerRoles()
    {
        $factory   = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders = $factory->getTrackerReminders();

        $this->assertCount(7, $reminders);
    }
}
