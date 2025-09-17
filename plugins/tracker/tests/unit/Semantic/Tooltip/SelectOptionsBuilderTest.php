<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Tooltip;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\FormElement\RetrieveFormElementsForTrackerStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SelectOptionsBuilderTest extends TestCase
{
    public function testWhenTrackerHasNoElements(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $root = (new SelectOptionsBuilder(
            RetrieveFormElementsForTrackerStub::withoutAnyElements(),
        ))->build($tracker, $user, []);

        self::assertEmpty($root->options);
        self::assertEmpty($root->optgroups);
    }

    public function testWhenTrackerHasUnreadableFields(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $assignto = $this->aSelectBoxField(123, 'Assigned to', false);

        $root = (new SelectOptionsBuilder(
            RetrieveFormElementsForTrackerStub::with(
                $assignto
            ),
        ))->build($tracker, $user, []);

        self::assertEmpty($root->options);
        self::assertEmpty($root->optgroups);
    }

    public function testWhenTheFieldShouldBeExcluded(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $assignto = $this->aSelectBoxField(123, 'Assigned to', true);

        $root = (new SelectOptionsBuilder(
            RetrieveFormElementsForTrackerStub::with(
                $assignto
            ),
        ))->build($tracker, $user, [123 => $assignto]);

        self::assertEmpty($root->options);
        self::assertEmpty($root->optgroups);
    }

    public function testWhenTheFieldCannotBeDisplayedInTooltip(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $stepdef = $this->createMock(\Tuleap\Tracker\FormElement\Field\TrackerField::class);
        $stepdef->method('userCanRead')->willReturn(true);
        $stepdef->method('getLabel')->willReturn('Step definition');
        $stepdef->method('getId')->willReturn(123);
        $stepdef->method('canBeDisplayedInTooltip')->willReturn(false);


        $root = (new SelectOptionsBuilder(
            RetrieveFormElementsForTrackerStub::with(
                $stepdef
            ),
        ))->build($tracker, $user, []);

        self::assertEmpty($root->options);
        self::assertEmpty($root->optgroups);
    }

    public function testWhenTrackerHasOneReadableField(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $assignto = $this->aSelectBoxField(123, 'Assigned to', true);
        $subby    = $this->aSelectBoxField(124, 'Submitted by', true);

        $root = (new SelectOptionsBuilder(
            RetrieveFormElementsForTrackerStub::with(
                $assignto,
                $subby,
            ),
        ))->build($tracker, $user, []);

        self::assertCount(2, $root->options);
        self::assertEquals(123, $root->options[0]->value);
        self::assertEquals('Assigned to', $root->options[0]->label);
        self::assertEquals(124, $root->options[1]->value);
        self::assertEquals('Submitted by', $root->options[1]->label);
        self::assertEmpty($root->optgroups);
    }

    public function testStaticFieldsAreIgnored(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $root = (new SelectOptionsBuilder(
            RetrieveFormElementsForTrackerStub::with(
                $this->aLineBreak(123)
            ),
        ))->build($tracker, $user, []);

        self::assertEmpty($root->options);
        self::assertEmpty($root->optgroups);
    }

    public function testFieldsInContainers(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        $assignto = $this->aSelectBoxField(123, 'Assigned to', true);
        $subby    = $this->aSelectBoxField(124, 'Submitted by', true);

        $details = $this->aFieldset(125, 'Details', true, [$assignto]);
        $info    = $this->aFieldset(126, 'Information', true, [$subby]);

        $root = (new SelectOptionsBuilder(
            RetrieveFormElementsForTrackerStub::with(
                $details,
                $info,
            ),
        ))->build($tracker, $user, []);

        self::assertEmpty($root->options);
        self::assertCount(2, $root->optgroups);

        self::assertEquals('Details', $root->optgroups[0]->label);
        self::assertCount(1, $root->optgroups[0]->options);
        self::assertEquals(123, $root->optgroups[0]->options[0]->value);
        self::assertEquals('Assigned to', $root->optgroups[0]->options[0]->label);

        self::assertEquals('Information', $root->optgroups[1]->label);
        self::assertCount(1, $root->optgroups[1]->options);
        self::assertEquals(124, $root->optgroups[1]->options[0]->value);
        self::assertEquals('Submitted by', $root->optgroups[1]->options[0]->label);
    }

    public function testContainersInContainers(): void
    {
        $tracker = TrackerTestBuilder::aTracker()->build();
        $user    = UserTestBuilder::buildWithDefaults();

        // * Assigned to
        // ┏━ Details ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┓
        // ┃ * Category                                ┃
        // ┃ ┏━ Information ━━━┓  ┏━ Stuff ━━━━━━━━━━┓ ┃
        // ┃ ┃ * Submitted by  ┃  ┃ * Line break     ┃ ┃
        // ┃ ┃ * Priority      ┃  ┃                  ┃ ┃
        // ┃ ┗━━━━━━━━━━━━━━━━━┛  ┗━━━━━━━━━━━━━━━━━━┛ ┃
        // ┗━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━┛

        $assignto  = $this->aSelectBoxField(123, 'Assigned to', true);
        $subby     = $this->aSelectBoxField(124, 'Submitted by', true);
        $priority  = $this->aSelectBoxField(125, 'Priority', true);
        $category  = $this->aSelectBoxField(126, 'Category', true);
        $linebreak = $this->aLineBreak(127);

        $info    = $this->aFieldset(128, 'Information', true, [$subby, $priority]);
        $stuff   = $this->aFieldset(129, 'Stuff', true, [$linebreak]);
        $details = $this->aFieldset(130, 'Details', true, [$category, $info, $stuff]);

        $root = (new SelectOptionsBuilder(
            RetrieveFormElementsForTrackerStub::with(
                $assignto,
                $details,
            ),
        ))->build($tracker, $user, []);

        self::assertCount(1, $root->options);
        self::assertEquals(123, $root->options[0]->value);
        self::assertEquals('Assigned to', $root->options[0]->label);

        self::assertCount(2, $root->optgroups);

        self::assertEquals('Details', $root->optgroups[0]->label);
        self::assertCount(1, $root->optgroups[0]->options);
        self::assertEquals(126, $root->optgroups[0]->options[0]->value);
        self::assertEquals('Category', $root->optgroups[0]->options[0]->label);

        self::assertEquals('Details::Information', $root->optgroups[1]->label);
        self::assertCount(2, $root->optgroups[1]->options);
        self::assertEquals(124, $root->optgroups[1]->options[0]->value);
        self::assertEquals('Submitted by', $root->optgroups[1]->options[0]->label);
        self::assertEquals(125, $root->optgroups[1]->options[1]->value);
        self::assertEquals('Priority', $root->optgroups[1]->options[1]->label);
    }

    private function aSelectBoxField(int $id, string $label, bool $readable): \Tuleap\Tracker\FormElement\Field\List\SelectboxField
    {
        $sb = $this->createMock(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
        $sb->method('userCanRead')->willReturn($readable);
        $sb->method('getLabel')->willReturn($label);
        $sb->method('getId')->willReturn($id);
        $sb->method('canBeDisplayedInTooltip')->willReturn(true);

        return $sb;
    }

    private function aFieldset(int $id, string $label, bool $readable, array $subelements): \Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer
    {
        $fieldset = $this->createMock(\Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer::class);
        $fieldset->method('userCanRead')->willReturn($readable);
        $fieldset->method('getLabel')->willReturn($label);
        $fieldset->method('getId')->willReturn($id);
        $fieldset->method('getFormElements')->willReturn($subelements);

        return $fieldset;
    }

    private function aLineBreak(int $id): \Tuleap\Tracker\FormElement\StaticField\LineBreak\LineBreakStaticField
    {
        $br = $this->createMock(\Tuleap\Tracker\FormElement\StaticField\LineBreak\LineBreakStaticField::class);
        $br->method('userCanRead')->willReturn(true);
        $br->method('getLabel')->willReturn('br');
        $br->method('getId')->willReturn($id);

        return $br;
    }
}
