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

namespace Tuleap\CrossTracker\Report\CSV\Format;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Field\Date\CSVFormatter;

final class CSVFormatterVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private CSVFormatterVisitor $visitor;
    private CSVFormatter&MockObject $date_formatter;
    private PFUser $user;
    private FormatterParameters $parameters;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user       = UserTestBuilder::aUser()->build();
        $this->parameters = new FormatterParameters($this->user);

        $this->date_formatter = $this->createMock(CSVFormatter::class);
        $this->visitor        = new CSVFormatterVisitor($this->date_formatter);
    }

    public function testVisitDateValue(): void
    {
        $date_value     = new DateValue(1540456782, true);
        $formatted_date = '25/10/2018 10:39';
        $this->date_formatter
            ->method('formatDateForCSVForUser')
            ->with($this->user, 1540456782, true)
            ->willReturn($formatted_date);

        $result = $date_value->accept($this->visitor, $this->parameters);

        self::assertEquals($formatted_date, $result);
    }

    public function testVisitTextValue(): void
    {
        $text_value = new TextValue('Kara "Starbuck" Thrace');

        $result = $text_value->accept($this->visitor, $this->parameters);

        self::assertEquals('"Kara ""Starbuck"" Thrace"', $result);
    }

    public function testVisitUserValue(): void
    {
        $starbuck = $this->createMock(PFUser::class);
        $starbuck->method('getUserName')->willReturn('starbuck');
        $user_value = new UserValue($starbuck);

        $result = $user_value->accept($this->visitor, $this->parameters);

        self::assertEquals('starbuck', $result);
    }

    public function testVisitNullUserValue(): void
    {
        $user_value = new UserValue(null);

        $result = $user_value->accept($this->visitor, $this->parameters);

        self::assertEquals('', $result);
    }

    public function testVisitNumericValue(): void
    {
        $numeric_value = new NumericValue(60.1342);

        $result = $numeric_value->accept($this->visitor, $this->parameters);

        self::assertEquals(60.1342, $result);
    }

    public function testVisitEmptyValue(): void
    {
        $empty_value = new EmptyValue();

        $result = $empty_value->accept($this->visitor, $this->parameters);

        self::assertEquals('', $result);
    }
}
