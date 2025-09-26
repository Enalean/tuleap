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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_List;
use Tracker_Artifact_ChangesetValue_OpenList;
use Tracker_FormElement_Field_List_BindValue;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\List\OpenListField;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\OpenListFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_Artifact_ChangesetValue_OpenListTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private OpenListField $field;
    private Tracker_Artifact_Changeset $changeset;


    #[\Override]
    protected function setUp(): void
    {
        $this->field     = OpenListFieldBuilder::anOpenListField()->build();
        $this->changeset = ChangesetTestBuilder::aChangeset(12)->build();
    }

    public function testLists(): void
    {
        $bind_value = ListStaticValueBuilder::aStaticValue('value')->withId(106)->build();

        $value_list = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value]);

        self::assertCount(1, $value_list);
        self::assertEquals($bind_value, $value_list[0]);
        self::assertEquals(['b106'], $value_list->getValue());
    }

    public function testDiffSetto(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $open_value_2 = $this->getBindValueForLabel('Manon');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(11, $this->changeset, $this->field, false, [$bind_value_1, $open_value_2]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, []);

        self::assertEquals(' set to Sandra, Manon', $list_1->diff($list_2));
    }

    public function testDiffChangedfrom(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $open_value_2 = $this->getBindValueForLabel('Manon');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$open_value_2]);

        self::assertEquals(' changed from Manon to Sandra', $list_1->diff($list_2));
        self::assertEquals(' changed from Sandra to Manon', $list_2->diff($list_1));
    }

    public function testDiffAdded(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $open_value_2 = $this->getBindValueForLabel('Manon');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1, $open_value_2]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1]);

        self::assertEquals('Manon added', $list_1->diff($list_2));
    }

    public function testDiffRemoved(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $open_value_2 = $this->getBindValueForLabel('Manon');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1, $open_value_2]);

        self::assertEquals('Manon removed', $list_1->diff($list_2));
    }

    public function testDiffCleared(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');

        $list_1 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, []);
        $list_2 = new Tracker_Artifact_ChangesetValue_List(111, $this->changeset, $this->field, false, [$bind_value_1]);

        self::assertEquals(' cleared values: Sandra', $list_1->diff($list_2));
    }

    public function testDiffAddedAndRemoved(): void
    {
        $bind_value_1 = $this->getBindValueForLabel('Sandra');
        $bind_value_2 = $this->getBindValueForLabel('Manon');
        $bind_value_3 = $this->getBindValueForLabel('Marc');
        $bind_value_4 = $this->getBindValueForLabel('Nicolas');

        $list_1 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_3, $bind_value_4]);
        $list_2 = new Tracker_Artifact_ChangesetValue_OpenList(111, $this->changeset, $this->field, false, [$bind_value_1, $bind_value_2]);

        $expected_diff = <<<EOT
        Sandra, Manon removed
        Marc, Nicolas added
        EOT;

        self::assertEquals($expected_diff, $list_1->diff($list_2));
    }

    private function getBindValueForLabel(string $value): Tracker_FormElement_Field_List_BindValue
    {
        return ListStaticValueBuilder::aStaticValue($value)->build();
    }
}
