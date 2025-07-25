<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field_List;
use TransitionFactory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueListTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class TransitionListValidatorTest extends TestCase
{
    private TransitionListValidator $transition_validator;
    private TransitionFactory&MockObject $transition_factory;

    public function setUp(): void
    {
        $this->transition_factory   = $this->createMock(TransitionFactory::class);
        $this->transition_validator = new TransitionListValidator($this->transition_factory);
    }

    public function testTransitionToParamIsCorrectlyExtractedForStringFields(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(65)->build();
        $field     = $this->createMock(Tracker_FormElement_Field_List::class);
        $value     = 'Closed';
        $tracker   = TrackerTestBuilder::aTracker()->build();
        $field->method('getId')->willReturn(2864);

        $changeset->setFieldValue(
            $field,
            ChangesetValueListTestBuilder::aListOfValue(1, $changeset, $field)->withValues([
                ListStaticValueBuilder::aStaticValue('Open')->build(),
                ListStaticValueBuilder::aStaticValue('Waiting for Information')->build(),
                ListStaticValueBuilder::aStaticValue('Closed')->build(),
            ])->build()
        );

        $this->transition_factory->method('getTransitionId')->with($tracker, 'Open', $value)->willReturn(10);

        $field->method('userCanMakeTransition')->with(10)->willReturn(true);
        $field->method('getTracker')->willReturn($tracker);

        self::assertTrue($this->transition_validator->checkTransition($field, $value, $changeset));
    }

    public function testTransitionToParamIsCorrectlyExtractedForListFields(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(65)->build();
        $field     = $this->createMock(Tracker_FormElement_Field_List::class);
        $field->method('getId')->willReturn(2864);
        $value   = ChangesetValueListTestBuilder::aListOfValue(101, $changeset, $field)->build();
        $tracker = TrackerTestBuilder::aTracker()->build();

        $changeset->setFieldValue(
            $field,
            ChangesetValueListTestBuilder::aListOfValue(1, $changeset, $field)->withValues([
                ListStaticValueBuilder::aStaticValue('Open')->build(),
                ListStaticValueBuilder::aStaticValue('Waiting for Information')->build(),
                ListStaticValueBuilder::aStaticValue('Closed')->build(),
            ])->build()
        );

        $this->transition_factory->method('getTransitionId')->with($tracker, 'Open', '101')->willReturn(10);

        $field->method('userCanMakeTransition')->with(10)->willReturn(true);
        $field->method('getTracker')->willReturn($tracker);

        self::assertTrue($this->transition_validator->checkTransition($field, $value, $changeset));
    }

    public function testTransitionIsInvalidWhenUserDoesNotHaveSufficientPermissions(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(65)->build();
        $field     = $this->createMock(Tracker_FormElement_Field_List::class);
        $value     = 'Closed';
        $tracker   = TrackerTestBuilder::aTracker()->build();
        $field->method('getId')->willReturn(2864);

        $changeset->setFieldValue(
            $field,
            ChangesetValueListTestBuilder::aListOfValue(1, $changeset, $field)->withValues([
                ListStaticValueBuilder::aStaticValue('Open')->build(),
                ListStaticValueBuilder::aStaticValue('Waiting for Information')->build(),
                ListStaticValueBuilder::aStaticValue('Closed')->build(),
            ])->build()
        );

        $this->transition_factory->method('getTransitionId')->with($tracker, 'Open', $value)->willReturn(10);

        $field->method('userCanMakeTransition')->with(10)->willReturn(false);
        $field->method('getTracker')->willReturn($tracker);

        self::assertFalse($this->transition_validator->checkTransition($field, $value, $changeset));
    }
}
