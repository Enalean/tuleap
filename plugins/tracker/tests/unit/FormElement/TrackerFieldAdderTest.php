<?php
/**
 * Copyright (c) Enalean, 2026-present. All Rights Reserved.
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

use Override;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;
use Tuleap\Tracker\Test\Stub\AddFieldStub;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerFieldAdderTest extends TestCase
{
    private AddFieldStub $form_element_factory;

    #[Override]
    protected function setUp(): void
    {
        $this->form_element_factory = AddFieldStub::build();
    }

    public function testItDoesNothingWhenTheCurrentFieldIsAlreadyUsed(): void
    {
        $field = StringFieldBuilder::aStringField(1)->build();

        $tracker_field_adder = new TrackerFieldAdder($this->form_element_factory);
        $tracker_field_adder->add($field);

        self::assertSame(0, $this->form_element_factory->call_count);
    }

    public function testItUpdatesTheUsageOfTheFieldIfTheFieldWasUnusedBefore(): void
    {
        $field = StringFieldBuilder::aStringField(1)->unused()->build();

        $tracker_field_adder = new TrackerFieldAdder($this->form_element_factory);
        $tracker_field_adder->add($field);

        self::assertSame(1, $this->form_element_factory->call_count);
    }
}
