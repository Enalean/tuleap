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

namespace Tuleap\TrackersV3ToV5;

use ArtifactType;
use ProjectManager;
use ProjectUGroup;
use Tracker;
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
use Tuleap\Disposable\Dispose;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Helpers\CodendiLogSwitcher;

final class DefectTrackerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private Tracker_FormElementFactory $form_element_factory;
    private Tracker $defect_tracker;
    private Tracker_ReportFactory $report_factory;
    private \Tracker_Report $bugs_report;

    protected function setUp(): void
    {
        \Tracker_Semantic_Title::clearInstances();
        \Tracker_Semantic_Status::clearInstances();
        \Tracker_Semantic_Contributor::clearInstances();
        $this->form_element_factory = Tracker_FormElementFactory::instance();
        $this->report_factory       = Tracker_ReportFactory::instance();
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testItConvertsBugTracker(): void
    {
        Dispose::using(new CodendiLogSwitcher(), function () {
            $db      = DBFactory::getMainTuleapDBConnection()->getDB();
            $results = $db->run("SELECT group_artifact_id FROM artifact_group_list WHERE item_name = 'bug' AND group_id = 100");
            if (count($results) !== 1) {
                throw new \RuntimeException('No Tracker v3 data. Migration impossible');
            }
            $row = $results[0];

            $defect_trackerv3_id = $row['group_artifact_id'];
            $v3_migration        = new Tracker_Migration_V3(TrackerFactory::instance());
            $project             = ProjectManager::instance()->getProject(100);
            $name                = 'Defect';
            $description         = 'defect tracker';
            $itemname            = 'defect';
            $tv3                 = new ArtifactType($project, $defect_trackerv3_id);

            $this->defect_tracker = $v3_migration->createTV5FromTV3($project, $name, $description, $itemname, $tv3);
            unset($GLOBALS['Language']);

            $this->bugs_report = $this->getReportByName('Bugs');

            $this->checkItCreatedTrackerV5WithDefaultParameters();
            $this->checkItHasNoParent();
            $this->checkItGivesFullAccessToAllUsers();
            $this->checkItHasATitleSemantic();
            $this->checkItHasAStatusSemantic();
            $this->checkItHasOnlyOneOpenValueForStatusSemantic();
            $this->checkItHasAnAssignedToSemantic();
            $this->checkItHasSubmittedBy();
            $this->checkItHasATextFieldDescription();
            $this->checkItHasAnUnusedDateFieldCloseDate();
            $this->checkItHasAnUnusedField();
            $this->checkItHasAListFieldResolutionWithValues();
            $this->checkItHasTwoReports();
            $this->checkItHasAReportNamedBugs();
            $this->checkItHasFourCriteria();
            $this->checkItHasATableRenderer();
        });
    }

    private function getReportByName($name)
    {
        foreach ($this->report_factory->getReportsByTrackerId($this->defect_tracker->getId(), null) as $report) {
            if ($report->name === $name) {
                return $report;
            }
        }
    }

    private function checkItCreatedTrackerV5WithDefaultParameters(): void
    {
        self::assertSame('Defect', $this->defect_tracker->getName());
        self::assertSame('defect tracker', $this->defect_tracker->getDescription());
        self::assertSame('defect', $this->defect_tracker->getItemName());
        self::assertSame('100', $this->defect_tracker->getGroupId());
    }

    private function checkItHasNoParent(): void
    {
        self::assertNull($this->defect_tracker->getParent());
    }

    private function checkItGivesFullAccessToAllUsers(): void
    {
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker::PERMISSION_FULL,
            ],
        ], $this->defect_tracker->getPermissionsByUgroupId());
    }

    private function checkItHasATitleSemantic(): void
    {
        $field = $this->defect_tracker->getTitleField();
        self::assertInstanceOf(Tracker_FormElement_Field_String::class, $field);
        self::assertSame('summary', $field->getName());
        self::assertSame('Summary', $field->getLabel());
        self::assertTrue($field->isRequired());
        self::assertTrue($field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS       => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::REGISTERED      => [
                Tracker_FormElement::PERMISSION_SUBMIT,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    private function checkItHasAStatusSemantic(): void
    {
        $field = $this->defect_tracker->getStatusField();
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame('status_id', $field->getName());
        self::assertSame('Status', $field->getLabel());
        self::assertTrue($field->isRequired());
        self::assertTrue($field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS       => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    private function checkItHasOnlyOneOpenValueForStatusSemantic(): void
    {
        $semantic_status = Tracker_SemanticFactory::instance()->getSemanticStatusFactory()->getByTracker(
            $this->defect_tracker
        );
        $open_values     = $semantic_status->getOpenValues();
        self::assertCount(1, $open_values);
        $open_value = $semantic_status->getField()->getListValueById($open_values[0]);
        self::assertSame('Open', $open_value->getLabel());
    }

    private function checkItHasAnAssignedToSemantic(): void
    {
        $field = $this->defect_tracker->getContributorField();
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame('assigned_to', $field->getName());
        self::assertSame('Assigned to', $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertTrue($field->isUsed());
        self::assertFalse($field->isMultiple());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS       => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::REGISTERED      => [
                Tracker_FormElement::PERMISSION_SUBMIT,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    private function checkItHasSubmittedBy(): void
    {
        $field = $this->form_element_factory->getFormElementByName($this->defect_tracker->getId(), 'submitted_by');
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame('submitted_by', $field->getName());
        self::assertSame('Submitted by', $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertTrue($field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    private function checkItHasATextFieldDescription(): void
    {
        $field = $this->form_element_factory->getFormElementByName($this->defect_tracker->getId(), 'details');
        self::assertInstanceOf(Tracker_FormElement_Field_Text::class, $field);
        self::assertSame('details', $field->getName());
        self::assertSame('Original Submission', $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertTrue($field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS       => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::REGISTERED      => [
                Tracker_FormElement::PERMISSION_SUBMIT,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    private function checkItHasAnUnusedDateFieldCloseDate(): void
    {
        $field = $this->form_element_factory->getFormElementByName($this->defect_tracker->getId(), 'close_date');
        self::assertInstanceOf(Tracker_FormElement_Field_Date::class, $field);
        self::assertSame('close_date', $field->getName());
        self::assertSame('Close Date', $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertFalse($field->isUsed());
        self::assertEqualsCanonicalizing([
            ProjectUGroup::ANONYMOUS       => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ], $field->getPermissionsByUgroupId());
    }

    private function checkItHasAnUnusedField(): void
    {
        $field = $this->form_element_factory->getFormElementByName($this->defect_tracker->getId(), 'originator_name');
        self::assertInstanceOf(Tracker_FormElement_Field_String::class, $field);
        self::assertSame('originator_name', $field->getName());
        self::assertSame('Originator Name', $field->getLabel());
        self::assertFalse($field->isUsed());
    }

    private function checkItHasAListFieldResolutionWithValues(): void
    {
        $field = $this->form_element_factory->getFormElementByName($this->defect_tracker->getId(), 'resolution_id');
        self::assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        self::assertSame('resolution_id', $field->getName());
        self::assertSame('Resolution', $field->getLabel());
        self::assertFalse($field->isRequired());
        self::assertTrue($field->isUsed());

        $this->compareValuesToLabel(
            $field->getAllValues(),
            ['Fixed', 'Invalid', 'Wont Fix', 'Later', 'Remind', 'Works for me', 'Duplicate']
        );
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

    private function checkItHasTwoReports(): void
    {
        self::assertCount(2, $this->report_factory->getReportsByTrackerId($this->defect_tracker->getId(), null));
    }

    private function checkItHasAReportNamedBugs(): void
    {
        self::assertSame('Bugs', $this->bugs_report->name);
    }

    private function checkItHasFourCriteria(): void
    {
        $criteria = $this->bugs_report->getCriteria();
        $this->thereAreCriteriaForFields($criteria, ['Category', 'Group', 'Assigned to', 'Status']);
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

    private function checkItHasATableRenderer(): void
    {
        $renderers = $this->bugs_report->getRenderers();
        self::assertCount(1, $renderers);

        $renderer = array_shift($renderers);
        self::assertInstanceOf(Tracker_Report_Renderer_Table::class, $renderer);

        $columns = $renderer->getTableColumns(false, true, false);
        $this->thereAreColumnsForFields(
            $columns,
            ['Submitted by', 'Submitted on', 'Artifact ID', 'Summary', 'Assigned to']
        );
    }

    private function thereAreColumnsForFields($columns, $field_labels): void
    {
        self::assertCount(count($field_labels), $columns);
        foreach ($field_labels as $label) {
            self::assertTrue($this->columnsContainOneColumnForField($columns, $label));
        }
    }

    private function columnsContainOneColumnForField($columns, $field_label): bool
    {
        foreach ($columns as $column) {
            if ($column['field']->getLabel() === $field_label) {
                return true;
            }
        }
        return false;
    }
}
