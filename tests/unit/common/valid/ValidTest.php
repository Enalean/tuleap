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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ValidTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testArgPropagate(): void
    {
        $v = new Valid();
        $v->disableFeedback();
        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->with('value')->once();
        $v->addRule($r);
        $v->validate('value');
    }

    public function testRetPropagate(): void
    {
        $v = new Valid();
        $v->disableFeedback();
        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->andReturns(true);
        $v->addRule($r);
        $this->assertTrue($v->validate('value'));
    }

    // If one fails, all test fails
    public function testOneFailAllFail(): void
    {
        $v = new Valid();
        $v->disableFeedback();

        $r1 = \Mockery::spy(\Rule::class);
        $r1->shouldReceive('isValid')->andReturns(true);
        $v->addRule($r1);

        $r2 = \Mockery::spy(\Rule::class);
        $r2->shouldReceive('isValid')->andReturns(false);
        $v->addRule($r2);

        $r3 = \Mockery::spy(\Rule::class);
        $r3->shouldReceive('isValid')->andReturns(true);
        $v->addRule($r3);

        $this->assertFalse($v->validate('value'));
    }

    // All conditions are tested
    public function testAllRulesChecked(): void
    {
        $v = new Valid();
        $v->disableFeedback();

        $r1 = \Mockery::spy(\Rule::class);
        $r1->shouldReceive('isValid')->once()->andReturns(true);
        $v->addRule($r1);

        $r2 = \Mockery::spy(\Rule::class);
        $r2->shouldReceive('isValid')->once()->andReturns(false);
        $v->addRule($r2);

        $r3 = \Mockery::spy(\Rule::class);
        $r3->shouldReceive('isValid')->once()->andReturns(true);
        $v->addRule($r3);

        $v->validate('value');
    }

    public function testDefaultErrorMessage(): void
    {
        $v = new Valid();
        $v->disableFeedback();

        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->andReturns(false);
        $r->shouldReceive('getErrorMessage')->once();
        $v->addRule($r);

        $v->validate('value');
    }

    public function testNoErrorMessage(): void
    {
        $v = new Valid();
        $v->disableFeedback();

        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->andReturns(false);
        $r->shouldReceive('getErrorMessage')->never();
        $v->addRule($r, 'warning', 'test');

        $v->validate('value');
    }

    public function testNotRequiredEmptyCall(): void
    {
        $r1 = \Mockery::spy(\Rule::class);
        $r1->shouldReceive('isValid')->never();
        $v1 = new Valid();
        $v1->disableFeedback();
        $v1->addRule($r1);
        $v1->validate('');

        $r2 = \Mockery::spy(\Rule::class);
        $r2->shouldReceive('isValid')->never();
        $v2 = new Valid();
        $v2->addRule($r2);
        $v2->validate(false);

        $r3 = \Mockery::spy(\Rule::class);
        $v3 = new Valid();
        $r3->shouldReceive('isValid')->never();
        $v3->addRule($r3);
        $v3->validate(null);
    }

    public function testRequiredEmptyCall(): void
    {
        $r1 = \Mockery::spy(\Rule::class);
        $r1->shouldReceive('isValid')->once();
        $v1 = new Valid();
        $v1->disableFeedback();
        $v1->required();
        $v1->addRule($r1);
        $v1->validate('');

        $r2 = \Mockery::spy(\Rule::class);
        $r2->shouldReceive('isValid')->once();
        $v2 = new Valid();
        $v2->disableFeedback();
        $v2->required();
        $v2->addRule($r2);
        $v2->validate(false);

        $r3 = \Mockery::spy(\Rule::class);
        $r3->shouldReceive('isValid')->once();
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
        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->andReturns(true);

        $v = new Valid();
        $v->disableFeedback();
        $v->required();
        $v->addRule($r);
        $this->assertFalse($v->validate(''));
    }

    public function testValueEmpty(): void
    {
        $v = new Valid();
        $this->assertTrue($v->isValueEmpty(''));
        $this->assertTrue($v->isValueEmpty(false));
        $this->assertTrue($v->isValueEmpty(null));
        $this->assertFalse($v->isValueEmpty(' '));
    }

    public function testNoFeedback(): void
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->disableFeedback();
        $v->shouldReceive('addFeedback')->never();

        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->andReturns(false);
        $v->addRule($r);

        $v->validate('value');
    }

    public function testFeedback(): void
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        // Need to call the constructore manually
        $v->__construct();
        $v->shouldReceive('addFeedback')->once();

        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->andReturns(false);
        $r->shouldReceive('getErrorMessage')->andReturns('error');
        $v->addRule($r);

        $v->validate('value');
    }

    public function testFeedbackErrorWhenRequired(): void
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        // Need to call the constructore manually
        $v->__construct();
        $v->required();
        $v->shouldReceive('addFeedback')->with('error', 'error message')->once();

        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->andReturns(false);
        $r->shouldReceive('getErrorMessage')->andReturns('error message');
        $v->addRule($r);

        $v->validate('value');
    }

    public function testFeedbackWarning(): void
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        // Need to call the constructore manually
        $v->__construct();
        $v->shouldReceive('addFeedback')->with('warning', 'error message')->once();

        $r = \Mockery::spy(\Rule::class);
        $r->shouldReceive('isValid')->andReturns(false);
        $r->shouldReceive('getErrorMessage')->andReturns('error message');
        $v->addRule($r);

        $v->validate('value');
    }

    public function testFeedbackGlobal(): void
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        // Need to call the constructore manually
        $v->__construct();
        $v->shouldReceive('addFeedback')->with('warning', 'custom message')->once();

        $v->setErrorMessage('custom message');

        // Built-in message
        $r1 = \Mockery::spy(\Rule::class);
        $r1->shouldReceive('isValid')->andReturns(false);
        $r1->shouldReceive('getErrorMessage')->andReturns('built-in error message');
        $v->addRule($r1);

        // Developer message
        $r2 = \Mockery::spy(\Rule::class);
        $r2->shouldReceive('isValid')->andReturns(false);
        $v->addRule($r2, 'Just in time message');

        $v->validate('value');
    }

    public function testFeedbackGlobalWithoutErrors(): void
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('addFeedback')->never();

        $v->setErrorMessage('custom message');

        // Built-in message
        $r1 = \Mockery::spy(\Rule::class);
        $r1->shouldReceive('isValid')->andReturns(true);
        $r1->shouldReceive('getErrorMessage')->andReturns('built-in error message');
        $v->addRule($r1);

        $v->validate('value');
    }
}
