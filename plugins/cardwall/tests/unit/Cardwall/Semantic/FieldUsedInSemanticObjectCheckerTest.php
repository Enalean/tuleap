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

namespace Tuleap\Cardwall\Semantic;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElement_Field;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldUsedInSemanticObjectCheckerTest extends TestCase
{
    private Tracker_FormElement_Field $field;
    private FieldUsedInSemanticObjectChecker $checker;
    private BackgroundColorDao&MockObject $background_dao;

    public function setUp(): void
    {
        $this->background_dao = $this->createMock(BackgroundColorDao::class);
        $this->checker        = new FieldUsedInSemanticObjectChecker($this->background_dao);
        $this->field          = IntFieldBuilder::anIntField(101)->build();
    }

    public function testItShouldReturnTrueIfFieldIsUsedInCardFieldSemantic(): void
    {
        $card_field1 = IntFieldBuilder::anIntField(100)->build();
        $card_field2 = IntFieldBuilder::anIntField(101)->build();
        $card_fields = [$card_field1, $card_field2];

        self::assertTrue($this->checker->isUsedInSemantic($this->field, $card_fields));
    }

    public function testItShouldReturnTrueIfFieldIsUsedInBackgroundColorSemantic(): void
    {
        $this->background_dao->method('isFieldUsedAsBackgroundColor')->willReturn(101);

        self::assertTrue($this->checker->isUsedInSemantic($this->field, []));
    }

    public function testItShouldShouldReturnFalseWhenFieldIsNotACardFieldAndNotABAckgroundColorField(): void
    {
        $card_field1 = IntFieldBuilder::anIntField(104)->build();
        $card_field2 = IntFieldBuilder::anIntField(105)->build();
        $card_fields = [$card_field1, $card_field2];

        $this->background_dao->method('isFieldUsedAsBackgroundColor')->willReturn(false);

        self::assertFalse($this->checker->isUsedInSemantic($this->field, $card_fields));
    }
}
