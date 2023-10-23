<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Rule_NoCr;
use Rule_String;
use Tracker_FormElement_Field_String;
use Tuleap\GlobalResponseMock;
use Tuleap\Tracker\Artifact\Artifact;

class TrackerFormElementFieldStringTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Artifact
     */
    private $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Rule_String
     */
    private $rule_string;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Rule_NoCr
     */
    private $rule_nocr;
    /**
     * @var Mockery\Mock|Tracker_FormElement_Field_String
     */
    private $string;

    public function setUp(): void
    {
        $this->string = Mockery::mock(Tracker_FormElement_Field_String::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->artifact    = Mockery::mock(Artifact::class);
        $this->rule_string = Mockery::mock(Rule_String::class);
        $this->rule_string->shouldReceive('isValid')->andReturns(true);

        $this->rule_nocr = Mockery::mock(Rule_NoCr::class);
    }

    public function testNoDefaultValue()
    {
        $this->string->shouldReceive('getProperty')->andReturn(null);
        $this->assertFalse($this->string->hasDefaultValue());
    }

    public function testDefaultValue()
    {
        $this->string->shouldReceive('getProperty')->with('default_value')->andReturns('foo');
        $this->assertTrue($this->string->hasDefaultValue());
        $this->assertEquals('foo', $this->string->getDefaultValue());
    }

    public function testIsValid()
    {
        $this->rule_nocr->shouldReceive('isValid')->andReturns(true);

        $this->string->shouldReceive('getRuleString')->andReturns($this->rule_string);
        $this->string->shouldReceive('getRuleNoCr')->andReturns($this->rule_nocr);
        $this->string->shouldReceive('getProperty')->andReturns(null);

        $this->assertTrue($this->string->isValid($this->artifact, "Du texte"));
    }

    public function testIsValidCr()
    {
        $this->rule_nocr->shouldReceive('isValid')->andReturns(false);

        $this->string->shouldReceive('getRuleString')->andReturns($this->rule_string);
        $this->string->shouldReceive('getRuleNoCr')->andReturns($this->rule_nocr);
        $this->string->shouldReceive('getProperty')->andReturns(null);

        $this->assertFalse($this->string->isValid($this->artifact, "Du texte \n sur plusieurs lignes"));
    }

    public function testItAcceptsStringRespectingMaxCharsProperty()
    {
        $this->rule_nocr->shouldReceive('isValid')->andReturns(true);

        $this->string->shouldReceive('getRuleString')->andReturns($this->rule_string);
        $this->string->shouldReceive('getRuleNoCr')->andReturns($this->rule_nocr);
        $this->string->shouldReceive('getProperty')->with('maxchars')->andReturn(6);

        $this->assertTrue($this->string->isValid($this->artifact, 'Tuleap'));
    }

    public function testItAcceptsStringWhenMaxCharsPropertyIsNotDefined()
    {
        $this->rule_nocr->shouldReceive('isValid')->andReturns(true);

        $this->string->shouldReceive('getRuleString')->andReturns($this->rule_string);
        $this->string->shouldReceive('getRuleNoCr')->andReturns($this->rule_nocr);
        $this->string->shouldReceive('getProperty')->with('maxchars')->andReturn(0);

        $this->assertTrue($this->string->isValid($this->artifact, 'Tuleap'));
    }

    public function testItRejectsStringNotRespectingMaxCharsProperty()
    {
        $this->rule_nocr->shouldReceive('isValid')->andReturns(true);

        $this->string->shouldReceive('getRuleString')->andReturns($this->rule_string);
        $this->string->shouldReceive('getRuleNoCr')->andReturns($this->rule_nocr);
        $this->string->shouldReceive('getProperty')->with('maxchars')->andReturn(1);

        $this->assertFalse($this->string->isValid($this->artifact, 'Tuleap'));
    }

    public function testGetFieldData()
    {
        $this->assertEquals('this is a string value', $this->string->getFieldData('this is a string value'));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    public function testItIsEmptyWhenThereIsNoContent()
    {
        $this->assertTrue($this->string->isEmpty('', $this->artifact));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    public function testItIsEmptyWhenThereIsOnlyWhitespaces()
    {
        $this->assertTrue($this->string->isEmpty('  ', $this->artifact));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    public function testItIsNotEmptyWhenThereIsContent()
    {
        $this->assertFalse($this->string->isEmpty('sdf', $this->artifact));
    }
}
