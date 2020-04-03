<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

declare(strict_types=1);

namespace Tuleap\Tracker;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_Artifact_ChangesetValue_OpenList;
use Tracker_FormElement_Field_List_BindValue;
use Tracker_FormElement_Field_OpenList;
use Tuleap\GlobalLanguageMock;

class Tracker_Artifact_ChangesetValue_OpenListTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalLanguageMock;
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_OpenList
     */
    private $field;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;


    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['Language']->shouldReceive('getText')
            ->withArgs(['plugin_tracker_artifact', 'changed_from'])
            ->andReturn('changed from');
        $GLOBALS['Language']->shouldReceive('getText')
            ->withArgs(['plugin_tracker_artifact', 'to'])
            ->andReturn('to');
        $GLOBALS['Language']->shouldReceive('getText')
            ->withArgs(['plugin_tracker_artifact', 'set_to'])
            ->andReturn('set to');
        $GLOBALS['Language']->shouldReceive('getText')
            ->withArgs(['plugin_tracker_artifact', 'added'])
            ->andReturn('added');
        $GLOBALS['Language']->shouldReceive('getText')
            ->withArgs(['plugin_tracker_artifact', 'removed'])
            ->andReturn('removed');


        $this->field = Mockery::mock(Tracker_FormElement_Field_OpenList::class);
        $this->user  = Mockery::mock(PFUser::class);

        $this->changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
    }

    public function testLists(): void
    {
        $bind_value = $this->getBindValueForLabel('b106');

        $value_list = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value]);

        $this->assertCount(1, $value_list);
        $this->assertEquals($bind_value, $value_list[0]);
        $this->assertEquals(['b106'], $value_list->getValue());
    }

    public function testDiffSetto(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $open_value_2 = $this->getBindValueForLabel('Manon');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(11, $this->changeset, $this->field, false, [$bind_value_1, $open_value_2]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, []);

        $this->assertEquals(' set to Sandra, Manon', $list_1->diff($list_2));
    }

    public function testDiffChangedfrom(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $open_value_2 = $this->getBindValueForLabel('Manon');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$open_value_2]);

        $this->assertEquals(' changed from Manon to Sandra', $list_1->diff($list_2));
        $this->assertEquals(' changed from Sandra to Manon', $list_2->diff($list_1));
    }

    public function testDiffAdded(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $open_value_2 = $this->getBindValueForLabel('Manon');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1, $open_value_2]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1]);

        $this->assertEquals('Manon added', $list_1->diff($list_2));
    }

    public function testDiffRemoved(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $open_value_2 = $this->getBindValueForLabel('Manon');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1, $open_value_2]);

        $this->assertEquals('Manon removed', $list_1->diff($list_2));
    }

    public function testDiffCleared(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');

        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, []);
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, [$bind_value_1]);

        $this->assertEquals(' cleared values: Sandra', $list_1->diff($list_2));
    }

    public function testDiffAddedAndRemoved(): void
    {
        $bind_value_1 = $this->getBindValueForLabel("Sandra");
        $bind_value_2 = $this->getBindValueForLabel("Manon");
        $bind_value_3 = $this->getBindValueForLabel("Marc");
        $bind_value_4 = $this->getBindValueForLabel("Nicolas");

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_3, $bind_value_4]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1, $bind_value_2]);

        $expected_diff = <<<EOT
        Sandra, Manon removed
        Marc, Nicolas added
        EOT;

        $this->assertEquals($expected_diff, $list_1->diff($list_2));
    }

    /**
     * @return \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_List_BindValue
     */
    private function getBindValueForLabel(string $value): Tracker_FormElement_Field_List_BindValue
    {
        $bind_value = Mockery::mock(Tracker_FormElement_Field_List_BindValue::class);
        $bind_value->shouldReceive('getJsonId')->andReturn($value);
        $bind_value->shouldReceive('getLabel')->andReturn($value);

        return $bind_value;
    }
}
