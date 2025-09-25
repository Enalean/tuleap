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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\Field\String;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Rule_NoCr;
use Rule_String;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerFormElementFieldStringTest extends TestCase
{
    use GlobalResponseMock;

    private Artifact $artifact;
    private Rule_String&MockObject $rule_string;
    private Rule_NoCr&MockObject $rule_nocr;
    private StringField&MockObject $string;

    #[\Override]
    public function setUp(): void
    {
        $this->string = $this->createPartialMock(
            StringField::class,
            ['getProperty', 'getRuleString', 'getRuleNoCr'],
        );

        $this->artifact    = ArtifactTestBuilder::anArtifact(4153)->build();
        $this->rule_string = $this->createMock(Rule_String::class);
        $this->rule_string->method('isValid')->willReturn(true);

        $this->rule_nocr = $this->createMock(Rule_NoCr::class);
    }

    public function testNoDefaultValue(): void
    {
        $this->string->method('getProperty')->willReturn(null);
        self::assertFalse($this->string->hasDefaultValue());
    }

    public function testDefaultValue(): void
    {
        $this->string->method('getProperty')->with('default_value')->willReturn('foo');
        self::assertTrue($this->string->hasDefaultValue());
        self::assertEquals('foo', $this->string->getDefaultValue());
    }

    public function testIsValid(): void
    {
        $this->rule_nocr->method('isValid')->willReturn(true);

        $this->string->method('getRuleString')->willReturn($this->rule_string);
        $this->string->method('getRuleNoCr')->willReturn($this->rule_nocr);
        $this->string->method('getProperty')->willReturn(null);

        self::assertTrue($this->string->isValid($this->artifact, 'Du texte'));
    }

    public function testIsValidCr(): void
    {
        $this->rule_nocr->method('isValid')->willReturn(false);

        $this->string->method('getRuleString')->willReturn($this->rule_string);
        $this->string->method('getRuleNoCr')->willReturn($this->rule_nocr);
        $this->string->method('getProperty')->willReturn(null);

        self::assertFalse($this->string->isValid($this->artifact, "Du texte \n sur plusieurs lignes"));
    }

    public function testItAcceptsStringRespectingMaxCharsProperty(): void
    {
        $this->rule_nocr->method('isValid')->willReturn(true);

        $this->string->method('getRuleString')->willReturn($this->rule_string);
        $this->string->method('getRuleNoCr')->willReturn($this->rule_nocr);
        $this->string->method('getProperty')->with('maxchars')->willReturn(6);

        self::assertTrue($this->string->isValid($this->artifact, 'Tuleap'));
    }

    public function testItAcceptsStringWhenMaxCharsPropertyIsNotDefined(): void
    {
        $this->rule_nocr->method('isValid')->willReturn(true);

        $this->string->method('getRuleString')->willReturn($this->rule_string);
        $this->string->method('getRuleNoCr')->willReturn($this->rule_nocr);
        $this->string->method('getProperty')->with('maxchars')->willReturn(0);

        self::assertTrue($this->string->isValid($this->artifact, 'Tuleap'));
    }

    public function testItRejectsStringNotRespectingMaxCharsProperty(): void
    {
        $this->rule_nocr->method('isValid')->willReturn(true);

        $this->string->method('getRuleString')->willReturn($this->rule_string);
        $this->string->method('getRuleNoCr')->willReturn($this->rule_nocr);
        $this->string->method('getProperty')->with('maxchars')->willReturn(1);

        self::assertFalse($this->string->isValid($this->artifact, 'Tuleap'));
    }

    public function testGetFieldData(): void
    {
        self::assertEquals('this is a string value', $this->string->getFieldData('this is a string value'));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    public function testItIsEmptyWhenThereIsNoContent(): void
    {
        self::assertTrue($this->string->isEmpty('', $this->artifact));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    public function testItIsEmptyWhenThereIsOnlyWhitespaces(): void
    {
        self::assertTrue($this->string->isEmpty('  ', $this->artifact));
    }

    /**
     * @see https://tuleap.net/plugins/tracker?aid=6449
     */
    public function testItIsNotEmptyWhenThereIsContent(): void
    {
        self::assertFalse($this->string->isEmpty('sdf', $this->artifact));
    }
}
