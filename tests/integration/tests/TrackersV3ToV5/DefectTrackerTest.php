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

class DefectTrackerTest extends TestIntegrationTestCase
{
    use GlobalLanguageMock;

    private static $backup_codendi_log;

    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var Tracker
     */
    private $defect_tracker;
    /**
     * @var int
     */
    private static $defect_tracker_id;
    /**
     * @var Tracker_ReportFactory
     */
    private $report_factory;
    /**
     * @var \Tracker_Report
     */
    private $bugs_report;

    /**
     * @beforeClass
     */
    public static function convertBugTracker(): void
    {
        self::$backup_codendi_log = \ForgeConfig::get('backup_codendi_log');
        \ForgeConfig::set('codendi_log', '/tmp');

        $db      = DBFactory::getMainTuleapDBConnection()->getDB();
        $results = $db->run('SELECT * FROM artifact_group_list WHERE item_name = "bug" AND group_id = 100');
        if (count($results) !== 1) {
            throw new \RuntimeException('No Tracker v3 data. Migration impossible');
        }
        $row = $results[0];

        $defect_trackerv3_id = $row['group_artifact_id'];
        $v3_migration        = new Tracker_Migration_V3(TrackerFactory::instance());
        $project             = ProjectManager::instance()->getProject(100);
        $name                = 'Defect';
        $description         = "defect tracker";
        $itemname            = "defect";
        $tv3                 = new ArtifactType($project, $defect_trackerv3_id);

        $defect_tracker          = $v3_migration->createTV5FromTV3($project, $name, $description, $itemname, $tv3);
        self::$defect_tracker_id = $defect_tracker->getId();
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

        $this->defect_tracker = $this->tracker_factory->getTrackerById(self::$defect_tracker_id);

        $this->report_factory = Tracker_ReportFactory::instance();
        $this->bugs_report    = $this->getReportByName('Bugs');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_SESSION']);
    }

    protected function getReportByName($name)
    {
        foreach ($this->report_factory->getReportsByTrackerId(self::$defect_tracker_id, null) as $report) {
            if ($report->name === $name) {
                return $report;
            }
        }
    }

    public function testItCreatedTrackerV5WithDefaultParameters()
    {
        $this->assertEquals($this->defect_tracker->getName(), 'Defect');
        $this->assertEquals($this->defect_tracker->getDescription(), 'defect tracker');
        $this->assertEquals($this->defect_tracker->getItemName(), 'defect');
        $this->assertEquals($this->defect_tracker->getGroupId(), 100);
    }

    public function testItHasNoParent()
    {
        $this->assertNull($this->defect_tracker->getParent());
    }

    public function testItGivesFullAccessToAllUsers()
    {
        $this->assertEquals($this->defect_tracker->getPermissionsByUgroupId(), [
            ProjectUGroup::ANONYMOUS => [
                Tracker::PERMISSION_FULL,
            ],
        ]);
    }

    public function testItHasATitleSemantic()
    {
        $field = $this->defect_tracker->getTitleField();
        $this->assertInstanceOf(Tracker_FormElement_Field_String::class, $field);
        $this->assertEquals($field->getName(), "summary");
        $this->assertEquals($field->getLabel(), "Summary");
        $this->assertEquals(1, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        $this->assertEquals($field->getPermissionsByUgroupId(), [
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::REGISTERED => [
                Tracker_FormElement::PERMISSION_SUBMIT,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ]);
    }

    public function testItHasAStatusSemantic()
    {
        $field = $this->defect_tracker->getStatusField();
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "status_id");
        $this->assertEquals($field->getLabel(), "Status");
        $this->assertEquals(1, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        $this->assertEquals($field->getPermissionsByUgroupId(), [
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ]);
    }

    public function testItHasOnlyOneOpenValueForStatusSemantic()
    {
        $semantic_status = Tracker_SemanticFactory::instance()->getSemanticStatusFactory()->getByTracker($this->defect_tracker);
        $open_values     = $semantic_status->getOpenValues();
        $this->assertCount(1, $open_values);
        $open_value = $semantic_status->getField()->getListValueById($open_values[0]);
        $this->assertEquals($open_value->getLabel(), 'Open');
    }

    public function testItHasAnAssignedToSemantic()
    {
        $field = $this->defect_tracker->getContributorField();
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "assigned_to");
        $this->assertEquals($field->getLabel(), "Assigned to");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        $this->assertFalse($field->isMultiple());
        $this->assertEquals($field->getPermissionsByUgroupId(), [
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::REGISTERED => [
                Tracker_FormElement::PERMISSION_SUBMIT,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ]);
    }

    public function testItHasSubmittedBy()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'submitted_by');
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "submitted_by");
        $this->assertEquals($field->getLabel(), "Submitted by");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        $this->assertEquals($field->getPermissionsByUgroupId(), [
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
        ]);
    }

    public function testItHasATextFieldDescription()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'details');
        $this->assertInstanceOf(Tracker_FormElement_Field_Text::class, $field);
        $this->assertEquals($field->getName(), "details");
        $this->assertEquals($field->getLabel(), "Original Submission");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());
        $this->assertEquals($field->getPermissionsByUgroupId(), [
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::REGISTERED => [
                Tracker_FormElement::PERMISSION_SUBMIT,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ]);
    }

    public function testItHasAnUnusedDateFieldCloseDate()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'close_date');
        $this->assertInstanceOf(Tracker_FormElement_Field_Date::class, $field);
        $this->assertEquals($field->getName(), "close_date");
        $this->assertEquals($field->getLabel(), "Close Date");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(0, $field->isUsed());
        $this->assertEquals($field->getPermissionsByUgroupId(), [
            ProjectUGroup::ANONYMOUS => [
                Tracker_FormElement::PERMISSION_READ,
            ],
            ProjectUGroup::PROJECT_MEMBERS => [
                Tracker_FormElement::PERMISSION_UPDATE,
            ],
        ]);
    }

    public function testItHasAnUnusedField()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'originator_name');
        $this->assertInstanceOf(Tracker_FormElement_Field_String::class, $field);
        $this->assertEquals($field->getName(), "originator_name");
        $this->assertEquals($field->getLabel(), "Originator Name");
        $this->assertEquals(0, $field->isUsed());
    }

    public function testItHasAListFieldResolutionWithValues()
    {
        $field = $this->form_element_factory->getFormElementByName(self::$defect_tracker_id, 'resolution_id');
        $this->assertInstanceOf(Tracker_FormElement_Field_List::class, $field);
        $this->assertEquals($field->getName(), "resolution_id");
        $this->assertEquals($field->getLabel(), "Resolution");
        $this->assertEquals(0, $field->isRequired());
        $this->assertEquals(1, $field->isUsed());

        $this->compareValuesToLabel($field->getAllValues(), ['Fixed', 'Invalid', 'Wont Fix', 'Later', 'Remind', 'Works for me', 'Duplicate']);
    }

    private function compareValuesToLabel(array $values, array $labels)
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
        $this->assertCount(2, $this->report_factory->getReportsByTrackerId(self::$defect_tracker_id, null));
    }

    public function testItHasAReportNamedBugs()
    {
        $this->assertEquals($this->bugs_report->name, 'Bugs');
    }

    public function testItHasFourCriteria()
    {
        $criteria = $this->bugs_report->getCriteria();
        $this->thereAreCriteriaForFields($criteria, ['Category', 'Group', 'Assigned to', 'Status']);
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
            if ($criterion->field->getLabel() === $field_label) {
                return true;
            }
        }
        return false;
    }

    public function testItHasATableRenderer()
    {
        $renderers = $this->bugs_report->getRenderers();
        $this->assertCount(1, $renderers);

        $renderer = array_shift($renderers);
        $this->assertInstanceOf(Tracker_Report_Renderer_Table::class, $renderer);

        $columns = $renderer->getTableColumns(false, true, false);
        $this->thereAreColumnsForFields($columns, ['Submitted by', 'Submitted on', 'Artifact ID', 'Summary', 'Assigned to']);
    }

    public function thereAreColumnsForFields($columns, $field_labels)
    {
        $this->assertCount(count($field_labels), $columns);
        foreach ($field_labels as $label) {
            $this->assertTrue($this->columnsContainOneColumnForField($columns, $label));
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
}
