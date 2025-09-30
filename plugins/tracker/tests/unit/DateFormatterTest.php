<?php
/**
 * Copyright (c) Enalean, 2014 - present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\FormElement\Field\Date\DateField;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_DateFormatterTest extends \Tuleap\Test\PHPUnit\TestCase  // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    use \Tuleap\GlobalResponseMock;

    private DateField&MockObject $field;

    private Tracker_FormElement_DateFormatter $date_formatter;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->field          = $this->createMock(DateField::class);
        $this->date_formatter = new Tracker_FormElement_DateFormatter($this->field);
    }

    public function testItFormatsTimestampInRightFormat(): void
    {
        $timestamp = 1409752174;
        $expected  = '2014-09-03';

        $this->assertEquals($expected, $this->date_formatter->formatDate($timestamp));
    }

    public function testItValidatesWellFormedValue(): void
    {
        $value = '2014-09-03';

        $this->assertTrue($this->date_formatter->validate($value));
    }

    public function testItDoesNotValidateNotWellFormedValue(): void
    {
        $value = '2014/09/03';
        $this->field->expects($this->once())->method('getLabel');

        $this->assertFalse($this->date_formatter->validate($value));
    }
}
