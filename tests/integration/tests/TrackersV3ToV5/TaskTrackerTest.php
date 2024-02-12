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

final class TaskTrackerTest extends TestIntegrationTestCase
{
    use GlobalLanguageMock;

    private static string|false $backup_codendi_log;

    private Tracker_FormElementFactory $form_element_factory;
    private static int $task_tracker_id;
    private Tracker $task_tracker;
    private Tracker_ReportFactory $report_factory;
    private \Tracker_Report $tasks_report;

    /**
     * @beforeClass
     */
    public static function convertTaskTracker(): void
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
    public static function resetForgeConfig(): void
    {
        \ForgeConfig::set('codendi_log', self::$backup_codendi_log);
    }

    protected function setUp(): void
    {
        $this->form_element_factory = Tracker_FormElementFactory::instance();
        $tracker_factory            = TrackerFactory::instance();

        $tasks_tracker =  $tracker_factory->getTrackerById(self::$task_tracker_id);
        if (! $tasks_tracker) {
            throw new \LogicException(
                sprintf('Expected to find tracker with id #%d but it was not found', self::$task_tracker_id)
            );
        }
        $this->task_tracker = $tasks_tracker;

        $this->report_factory = Tracker_ReportFactory::instance();
        $this->tasks_report   = $this->getReportByName('Tasks');
    }

    private function getReportByName($name): \Tracker_Report
    {
        foreach ($this->report_factory->getReportsByTrackerId(self::$task_tracker_id, null) as $report) {
            if ($report->name === $name) {
                return $report;
            }
        }
        throw new \LogicException('Could not find the report');
    }

    public function testItCreatedTrackerV5WithDefaultParameters(): void
    {
        self::assertSame('Tasks', $this->task_tracker->getName());
        self::assertSame('tasks tracker', $this->task_tracker->getDescription());
        self::assertSame('tsk', $this->task_tracker->getItemName());
        self::assertSame('100', $this->task_tracker->getGroupId());
    }

    public function testItHasNoParent(): void
    {
        self::assertNull($this->task_tracker->getParent());
    }

    public function testItGivesFullAccessToAllUsers(): void
    {
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [Tracker::PERMISSION_FULL,],
        ], $this->task_tracker->getPermissionsByUgroupId());
    }

    public function testItHasATitleSemantic(): void
    {
        $field = $this->task_tracker->getTitleField();
        self::assertInstanceOf(Tracker_FormElement_Field_String::class, $field);
        self::assertSame("summary", $field->getName());
        self::assertSame("Summary", $field->getLabel());
        self::assertTrue($field->isRequired());
        self::assertTrue($field->isUsed());
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

    public function testItHasAStatusSemantic(): void
    {
        $field = $this->task_tracker->getStatusField();
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame("status_id", $field->getName());
        self::assertSame("Status", $field->getLabel());
        self::assertTrue($field->isRequired());
        self::assertTrue($field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    public function testItHasOnlyOneOpenValueForStatusSemantic(): void
    {
        $semantic_status = Tracker_SemanticFactory::instance()->getSemanticStatusFactory()->getByTracker($this->task_tracker);
        $open_values     = $semantic_status->getOpenValues();
        self::assertCount(1, $open_values);
        $open_value = $semantic_status->getField()->getListValueById($open_values[0]);
        self::assertSame('Open', $open_value->getLabel());
    }

    public function testItHasAnAssignedToSemantic(): void
    {
        $field = $this->task_tracker->getContributorField();
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame("multi_assigned_to", $field->getName());
        self::assertSame("Assigned to (multiple)", $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertTrue($field->isUsed());
        self::assertTrue($field->isMultiple());
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

    public function testItHasSubmittedBy(): void
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'submitted_by');
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame("submitted_by", $field->getName());
        self::assertSame("Submitted by", $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertTrue($field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    public function testItHasATextFieldDescription(): void
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'details');
        self::assertInstanceOf(Tracker_FormElement_Field_Text::class, $field);
        self::assertSame("details", $field->getName());
        self::assertSame("Original Submission", $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertTrue($field->isUsed());
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

    public function testItHasADateFieldStartDate(): void
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        self::assertInstanceOf(Tracker_FormElement_Field_Date::class, $field);
        self::assertSame("start_date", $field->getName());
        self::assertSame("Start Date", $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertTrue($field->isUsed());
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

    public function testItHasAnUnusedField(): void
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'stage');
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame("stage", $field->getName());
        self::assertSame("Stage", $field->getLabel());
        self::assertFalse($field->isUsed());
    }

    public function testItHasAListFieldResolutionWithValues(): void
    {
        $field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'severity');
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame("severity", $field->getName());
        self::assertSame("Priority", $field->getLabel());
        self::assertTrue($field->isRequired());
        self::assertTrue($field->isUsed());

        $this->compareValuesToLabel($field->getAllValues(), ['1 - Lowest', '2', '3', '4', '5 - Medium', '6', '7', '8', '9 - Highest']);
    }

    private function compareValuesToLabel(array $values, array $labels): void
    {
        self::assertCount(count($labels), $values);
        $i = 0;
        while ($value = array_shift($values)) {
            self::assertInstanceOf(Tracker_FormElement_Field_List_Bind_StaticValue::class, $value);
            self::assertSame($labels[$i++], $value->getLabel());
            self::assertSame('0', $value->isHidden());
        }
    }

    public function testItHasTwoReports(): void
    {
        self::assertCount(2, $this->report_factory->getReportsByTrackerId(self::$task_tracker_id, null));
    }

    public function testItHasAReportNamedBugs(): void
    {
        self::assertSame('Tasks', $this->tasks_report->name);
    }

    public function testItHasThreeCriteria(): void
    {
        $criteria = $this->tasks_report->getCriteria();
        $this->thereAreCriteriaForFields($criteria, ['Subproject', 'Assigned to (multiple)', 'Status']);
    }

    private function thereAreCriteriaForFields(array $criteria, array $field_labels): void
    {
        self::assertCount(count($field_labels), $criteria);
        foreach ($field_labels as $label) {
            self::assertTrue($this->criteriaContainOneCriterionForField($criteria, $label));
        }
    }

    private function criteriaContainOneCriterionForField(array $criteria, $field_label): bool
    {
        foreach ($criteria as $criterion) {
            if ($criterion->field->getLabel() === $field_label) {
                return true;
            }
        }
        return false;
    }

    public function testItHasATableRenderer(): void
    {
        $renderers = $this->tasks_report->getRenderers();
        self::assertCount(1, $renderers);

        $renderer = array_shift($renderers);
        self::assertInstanceOf(Tracker_Report_Renderer_Table::class, $renderer);

        $columns = $renderer->getTableColumns(false, true, false);
        $this->thereAreColumnsForFields($columns, ['Artifact ID', 'Assigned to (multiple)', 'Subproject', 'Effort', 'Status', 'Start Date', 'Summary']);
    }

    public function thereAreColumnsForFields($columns, $field_labels): void
    {
        self::assertCount(count($field_labels), $columns);
        foreach ($field_labels as $label) {
            self::assertTrue($this->columnsContainOneColumnForField($columns, $label));
        }
    }

    public function columnsContainOneColumnForField($columns, $field_label): bool
    {
        foreach ($columns as $column) {
            if ($column['field']->getLabel() === $field_label) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return \Tracker_DateReminder[]
     */
    private function findReminders(int $expected_field_id, int $expected_distance, int $expected_type): array
    {
        $factory = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );

        return \Psl\Vec\filter(
            $factory->getTrackerReminders(),
            static fn(\Tracker_DateReminder $reminder) => $reminder->getFieldId() === $expected_field_id
                && $reminder->getDistance() === $expected_distance
                && $reminder->getNotificationType() === $expected_type
        );
    }

    public function testItSendsAnEmailToProjectAndTrackerAdminsTwoDaysBeforeStartDate(): void
    {
        $start_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        $reminders        = $this->findReminders($start_date_field->getId(), 2, Tracker_DateReminder::BEFORE);

        self::assertCount(1, $reminders);
        self::assertSame(2, $reminders[0]->getDistance());
        self::assertSame(Tracker_DateReminder::BEFORE, $reminders[0]->getNotificationType());
        self::assertEquals($start_date_field, $reminders[0]->getField());
        self::assertSame(Tracker_DateReminder::ENABLED, $reminders[0]->getStatus());
        self::assertEqualsCanonicalizing([ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::TRACKER_ADMIN], $reminders[0]->getUgroups(true));
        self::assertEmpty($reminders[0]->getRoles());
    }

    public function testItSendsASecondEmailOnStartDate(): void
    {
        $start_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        $reminders        = $this->findReminders($start_date_field->getId(), 0, Tracker_DateReminder::BEFORE);

        self::assertCount(1, $reminders);
        self::assertSame(0, $reminders[0]->getDistance());
        self::assertSame(Tracker_DateReminder::BEFORE, $reminders[0]->getNotificationType());
        self::assertEquals($start_date_field, $reminders[0]->getField());
        self::assertSame(Tracker_DateReminder::ENABLED, $reminders[0]->getStatus());
        self::assertEqualsCanonicalizing([ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::TRACKER_ADMIN], $reminders[0]->getUgroups(true));
        self::assertEmpty($reminders[0]->getRoles());
    }

    public function testItSendsTheLastEmailTwoDaysAfterStartDate(): void
    {
        $start_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'start_date');
        $reminders        = $this->findReminders($start_date_field->getId(), 2, Tracker_DateReminder::AFTER);

        self::assertCount(1, $reminders);
        self::assertSame(2, $reminders[0]->getDistance());
        self::assertSame(Tracker_DateReminder::AFTER, $reminders[0]->getNotificationType());
        self::assertEquals($start_date_field, $reminders[0]->getField());
        self::assertSame(Tracker_DateReminder::ENABLED, $reminders[0]->getStatus());
        self::assertEqualsCanonicalizing([ProjectUGroup::PROJECT_ADMIN, ProjectUGroup::TRACKER_ADMIN], $reminders[0]->getUgroups(true));
        self::assertEmpty($reminders[0]->getRoles());
    }

    public function testItSendsAnEmailToProjectMembersAndSubmitterOneDayAfterEndDate(): void
    {
        $end_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'end_date');
        $submitterRole  = new Tracker_DateReminder_Role_Submitter();
        $notified_roles = [$submitterRole];
        $reminders      = $this->findReminders($end_date_field->getId(), 1, Tracker_DateReminder::AFTER);

        self::assertCount(1, $reminders);
        self::assertSame(1, $reminders[0]->getDistance());
        self::assertSame(Tracker_DateReminder::AFTER, $reminders[0]->getNotificationType());
        self::assertEquals($end_date_field, $reminders[0]->getField());
        self::assertSame(Tracker_DateReminder::ENABLED, $reminders[0]->getStatus());
        self::assertEquals([ProjectUGroup::PROJECT_MEMBERS], $reminders[0]->getUgroups(true));
        self::assertEquals($notified_roles, $reminders[0]->getRoles());
    }

    public function testItSendsAnEmailToProjectMembersAndSubmitterThreeDaysAfterEndDate(): void
    {
        $end_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'end_date');
        $submitterRole  = new Tracker_DateReminder_Role_Submitter();
        $notified_roles = [$submitterRole];
        $reminders      = $this->findReminders($end_date_field->getId(), 3, Tracker_DateReminder::AFTER);

        self::assertCount(1, $reminders);
        self::assertSame(3, $reminders[0]->getDistance());
        self::assertSame(Tracker_DateReminder::AFTER, $reminders[0]->getNotificationType());
        self::assertEquals($end_date_field, $reminders[0]->getField());
        self::assertSame(Tracker_DateReminder::ENABLED, $reminders[0]->getStatus());
        self::assertEquals([ProjectUGroup::PROJECT_MEMBERS], $reminders[0]->getUgroups(true));
        self::assertEquals($notified_roles, $reminders[0]->getRoles());
    }

    public function testItSendsAnEmailToSubmitterOneDaysAfterDueDate(): void
    {
        $due_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'due_date');
        $submitterRole  = new Tracker_DateReminder_Role_Submitter();
        $notified_roles = [$submitterRole];
        $reminders      = $this->findReminders($due_date_field->getId(), 1, Tracker_DateReminder::AFTER);

        self::assertCount(1, $reminders);
        self::assertSame(1, $reminders[0]->getDistance());
        self::assertSame(Tracker_DateReminder::AFTER, $reminders[0]->getNotificationType());
        self::assertEquals($due_date_field, $reminders[0]->getField());
        self::assertSame(Tracker_DateReminder::ENABLED, $reminders[0]->getStatus());
        self::assertEquals([""], $reminders[0]->getUgroups(true));
        self::assertEquals($notified_roles, $reminders[0]->getRoles());
    }

    public function testItSendsASecondEmailThreeDaysAfterDueDate(): void
    {
        $due_date_field = $this->form_element_factory->getFormElementByName(self::$task_tracker_id, 'due_date');
        $submitterRole  = new Tracker_DateReminder_Role_Submitter();
        $notified_roles = [$submitterRole];
        $reminders      = $this->findReminders($due_date_field->getId(), 3, Tracker_DateReminder::AFTER);

        self::assertCount(1, $reminders);
        self::assertSame(3, $reminders[0]->getDistance());
        self::assertSame(Tracker_DateReminder::AFTER, $reminders[0]->getNotificationType());
        self::assertEquals($due_date_field, $reminders[0]->getField());
        self::assertSame(Tracker_DateReminder::ENABLED, $reminders[0]->getStatus());
        self::assertEquals([""], $reminders[0]->getUgroups(true));
        self::assertEquals($notified_roles, $reminders[0]->getRoles());
    }

    public function testItCreateReminderWhenTheListOfUgroupsIsEmptyButNotTheTrackerRoles(): void
    {
        $factory   = new Tracker_DateReminderFactory(
            $this->task_tracker,
            new Tracker_DateReminderRenderer($this->task_tracker),
            new DateReminderDao(),
        );
        $reminders = $factory->getTrackerReminders();

        self::assertCount(7, $reminders);
    }
}
