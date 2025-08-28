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

namespace Tuleap\Cardwall;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\Cardwall\Semantic\FieldUsedInSemanticObjectChecker;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\TrackerField;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AllowedFieldRetrieverTest extends TestCase
{
    private TrackerField $field;
    private FieldUsedInSemanticObjectChecker&MockObject $checker;
    private AllowedFieldRetriever $allowed_field_retriever;
    private Tracker_FormElementFactory&MockObject $form_element_factory;

    #[\Override]
    public function setUp(): void
    {
        $this->checker                 = $this->createMock(FieldUsedInSemanticObjectChecker::class);
        $this->form_element_factory    = $this->createMock(Tracker_FormElementFactory::class);
        $this->allowed_field_retriever = new AllowedFieldRetriever($this->form_element_factory, $this->checker);

        $this->field = IntegerFieldBuilder::anIntField(153)->build();
    }

    public function testReturnsAndEmptyArrayIfFieldIsNotUsedForBackgroundSemantic(): void
    {
        $this->checker->method('isUsedInBackgroundColorSemantic')->willReturn(false);
        self::assertEquals([], $this->allowed_field_retriever->retrieveAllowedFieldType($this->field));
    }

    public function testItOnlyAllowsRbWhenFieldIsASb(): void
    {
        $this->checker->method('isUsedInBackgroundColorSemantic')->willReturn(true);
        $this->form_element_factory->method('getType')->willReturn('sb');
        self::assertEquals(['rb'], $this->allowed_field_retriever->retrieveAllowedFieldType($this->field));
    }

    public function testItOnlyAllowsSbWhenFieldIsARb(): void
    {
        $this->checker->method('isUsedInBackgroundColorSemantic')->willReturn(true);
        $this->form_element_factory->method('getType')->willReturn('rb');
        self::assertEquals(['sb'], $this->allowed_field_retriever->retrieveAllowedFieldType($this->field));
    }

    public function testReturnsAndEmptyArrayByDefault(): void
    {
        $this->checker->method('isUsedInBackgroundColorSemantic')->willReturn(true);
        $this->form_element_factory->method('getType')->willReturn('msb');
        self::assertEquals([], $this->allowed_field_retriever->retrieveAllowedFieldType($this->field));
    }
}
