<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\FormElement\Field\Date\CSVFormatter;

class CSVFormatterVisitorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var CSVFormatterVisitor */
    private $visitor;
    /** @var Mockery\MockInterface */
    private $date_formatter;

    protected function setUp()
    {
        parent::setUp();

        $this->date_formatter = Mockery::mock(CSVFormatter::class);
        $this->visitor        = new CSVFormatterVisitor($this->date_formatter);
    }

    public function testVisitDateValue()
    {
        $user           = Mockery::mock(PFUser::class);
        $date_value     = new DateValue(1540456782, true);
        $formatted_date = '25/10/2018 10:39';
        $this->date_formatter->shouldReceive('formatDateForCSVForUser')
            ->withArgs([$user, 1540456782, true])
            ->andReturn($formatted_date);
        $parameters = new FormatterParameters($user);

        $result = $date_value->accept($this->visitor, $parameters);

        $this->assertEquals($formatted_date, $result);
    }

    public function testVisitTextValue()
    {
        $user       = Mockery::mock(PFUser::class);
        $parameters = new FormatterParameters($user);
        $text_value = new TextValue('Kara "Starbuck" Thrace');

        $result = $text_value->accept($this->visitor, $parameters);

        $this->assertEquals('"Kara ""Starbuck"" Thrace"', $result);
    }

    public function testVisitUserValue()
    {
        $user       = Mockery::mock(PFUser::class);
        $parameters = new FormatterParameters($user);

        $starbuck = Mockery::mock(PFUser::class);
        $starbuck->shouldReceive('getUserName')->andReturns('starbuck');
        $user_value = new UserValue($starbuck);

        $result = $user_value->accept($this->visitor, $parameters);

        $this->assertEquals('starbuck', $result);
    }

    public function testVisitNullUserValue()
    {
        $user       = Mockery::mock(PFUser::class);
        $parameters = new FormatterParameters($user);
        $user_value = new UserValue(null);

        $result = $user_value->accept($this->visitor, $parameters);

        $this->assertEquals('', $result);
    }
}
