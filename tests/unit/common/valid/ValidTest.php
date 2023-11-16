<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class ValidTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testArgPropagate(): void
    {
        $v = new Valid();
        $v->disableFeedback();
        $r = $this->createMock(\Rule::class);
        $r->expects(self::once())->method('isValid')->with('value');
        $r->expects(self::once())->method('getErrorMessage')->willReturn('error');
        $v->addRule($r);
        $v->validate('value');
    }

    public function testRetPropagate(): void
    {
        $v = new Valid();
        $v->disableFeedback();
        $r = $this->createMock(\Rule::class);
        $r->method('isValid')->willReturn(true);
        $v->addRule($r);
        self::assertTrue($v->validate('value'));
    }

    // If one fails, all test fails
    public function testOneFailAllFail(): void
    {
        $v = new Valid();
        $v->disableFeedback();

        $r1 = $this->createMock(\Rule::class);
        $r1->method('isValid')->willReturn(true);
        $v->addRule($r1);

        $r2 = $this->createMock(\Rule::class);
        $r2->method('isValid')->willReturn(false);
        $r2->method('getErrorMessage')->willReturn('error');
        $v->addRule($r2);

        $r3 = $this->createMock(\Rule::class);
        $r3->method('isValid')->willReturn(true);
        $v->addRule($r3);

        self::assertFalse($v->validate('value'));
    }

    // All conditions are tested
    public function testAllRulesChecked(): void
    {
        $v = new Valid();
        $v->disableFeedback();

        $r1 = $this->createMock(\Rule::class);
        $r1->expects(self::once())->method('isValid')->willReturn(true);
        $v->addRule($r1);

        $r2 = $this->createMock(\Rule::class);
        $r2->expects(self::once())->method('isValid')->willReturn(false);
        $r2->method('getErrorMessage')->willReturn('error');
        $v->addRule($r2);

        $r3 = $this->createMock(\Rule::class);
        $r3->expects(self::once())->method('isValid')->willReturn(true);
        $v->addRule($r3);

        $v->validate('value');
    }

    public function testDefaultErrorMessage(): void
    {
        $v = new Valid();
        $v->disableFeedback();

        $r = $this->createMock(\Rule::class);
        $r->method('isValid')->willReturn(false);
        $r->expects(self::once())->method('getErrorMessage');
        $v->addRule($r);

        $v->validate('value');
    }

    public function testNoErrorMessage(): void
    {
        $v = new Valid();
        $v->disableFeedback();

        $r = $this->createMock(\Rule::class);
        $r->method('isValid')->willReturn(false);
        $r->expects(self::never())->method('getErrorMessage');
        $v->addRule($r, 'warning', 'test');

        $v->validate('value');
    }

    public function testNotRequiredEmptyCall(): void
    {
        $r1 = $this->createMock(\Rule::class);
        $r1->expects(self::never())->method('isValid');
        $v1 = new Valid();
        $v1->disableFeedback();
        $v1->addRule($r1);
        $v1->validate('');

        $r2 = $this->createMock(\Rule::class);
        $r2->expects(self::never())->method('isValid');
        $v2 = new Valid();
        $v2->addRule($r2);
        $v2->validate(false);

        $r3 = $this->createMock(\Rule::class);
        $v3 = new Valid();
        $r3->expects(self::never())->method('isValid');
        $v3->addRule($r3);
        $v3->validate(null);
    }

    public function testRequiredEmptyCall(): void
    {
        $r1 = $this->createMock(\Rule::class);
        $r1->expects(self::once())->method('isValid');
        $r1->method('getErrorMessage')->willReturn('error');
        $v1 = new Valid();
        $v1->disableFeedback();
        $v1->required();
        $v1->addRule($r1);
        $v1->validate('');

        $r2 = $this->createMock(\Rule::class);
        $r2->expects(self::once())->method('isValid');
        $r2->method('getErrorMessage')->willReturn('error');
        $v2 = new Valid();
        $v2->disableFeedback();
        $v2->required();
        $v2->addRule($r2);
        $v2->validate(false);

        $r3 = $this->createMock(\Rule::class);
        $r3->expects(self::once())->method('isValid');
        $r3->method('getErrorMessage')->willReturn('error');
        $v3 = new Valid();
        $v3->disableFeedback();
        $v3->required();
        $v3->addRule($r3);
        $v3->validate(null);
    }

    /**
     * Need to throw an error if the value is required but the rule return true
     * even with empty values
     */
    public function testRequiredAndPermissive(): void
    {
        $r = $this->createMock(\Rule::class);
        $r->method('isValid')->willReturn(true);

        $v = new Valid();
        $v->disableFeedback();
        $v->required();
        $v->addRule($r);
        self::assertFalse($v->validate(''));
    }

    public function testValueEmpty(): void
    {
        $v = new Valid();
        self::assertTrue($v->isValueEmpty(''));
        self::assertTrue($v->isValueEmpty(false));
        self::assertTrue($v->isValueEmpty(null));
        self::assertFalse($v->isValueEmpty(' '));
    }

    public function testNoFeedback(): void
    {
        $v = $this->getMockBuilder(\Valid::class)->onlyMethods(['addFeedback'])->getMock();
        $v->disableFeedback();
        $v->expects(self::never())->method('addFeedback');

        $r = $this->createMock(\Rule::class);
        $r->method('isValid')->willReturn(false);
        $r->method('getErrorMessage')->willReturn('error');
        $v->addRule($r);

        $v->validate('value');
    }

    public function testFeedback(): void
    {
        $v = $this->getMockBuilder(\Valid::class)->onlyMethods(['addFeedback'])->getMock();
        $v->expects(self::once())->method('addFeedback');

        $r = $this->createMock(\Rule::class);
        $r->method('isValid')->willReturn(false);
        $r->method('getErrorMessage')->willReturn('error');
        $v->addRule($r);

        $v->validate('value');
    }

    public function testFeedbackErrorWhenRequired(): void
    {
        $v = $this->getMockBuilder(\Valid::class)->onlyMethods(['addFeedback'])->getMock();
        // Need to call the constructore manually
        $v->required();
        $v->expects(self::once())->method('addFeedback')->with('error', 'error message');

        $r = $this->createMock(\Rule::class);
        $r->method('isValid')->willReturn(false);
        $r->method('getErrorMessage')->willReturn('error message');
        $v->addRule($r);

        $v->validate('value');
    }

    public function testFeedbackWarning(): void
    {
        $v = $this->getMockBuilder(\Valid::class)->onlyMethods(['addFeedback'])->getMock();
        $v->expects(self::once())->method('addFeedback')->with('warning', 'error message');

        $r = $this->createMock(\Rule::class);
        $r->method('isValid')->willReturn(false);
        $r->method('getErrorMessage')->willReturn('error message');
        $v->addRule($r);

        $v->validate('value');
    }

    public function testFeedbackGlobal(): void
    {
        $v = $this->getMockBuilder(\Valid::class)->onlyMethods(['addFeedback'])->getMock();
        $v->expects(self::once())->method('addFeedback')->with('warning', 'custom message');

        $v->setErrorMessage('custom message');

        // Built-in message
        $r1 = $this->createMock(\Rule::class);
        $r1->method('isValid')->willReturn(false);
        $r1->method('getErrorMessage')->willReturn('built-in error message');
        $v->addRule($r1);

        // Developer message
        $r2 = $this->createMock(\Rule::class);
        $r2->method('isValid')->willReturn(false);
        $v->addRule($r2, 'Just in time message');

        $v->validate('value');
    }

    public function testFeedbackGlobalWithoutErrors(): void
    {
        $v = $this->getMockBuilder(\Valid::class)->onlyMethods(['addFeedback'])->getMock();
        $v->expects(self::never())->method('addFeedback');

        $v->setErrorMessage('custom message');

        // Built-in message
        $r1 = $this->createMock(\Rule::class);
        $r1->method('isValid')->willReturn(true);
        $r1->method('getErrorMessage')->willReturn('built-in error message');
        $v->addRule($r1);

        $v->validate('value');
    }
}
