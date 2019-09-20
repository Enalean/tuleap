<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

Mock::generate('Rule');
Mock::generatePartial('Valid', 'ValidTestVersion', array('addFeedback'));

class ValidTest extends TuleapTestCase
{

    function testArgPropagate()
    {
        $v = new Valid();
        $v->disableFeedback();
        $r = new MockRule($this);
        $r->expectOnce('isValid', array('value'));
        $v->addRule($r);
        $v->validate('value');
    }

    function testRetPropagate()
    {
        $v = new Valid();
        $v->disableFeedback();
        $r = new MockRule($this);
        $r->setReturnValue('isValid', true);
        $v->addRule($r);
        $this->assertTrue($v->validate('value'));
    }

    // If one fails, all test fails
    function testOneFailAllFail()
    {
        $v = new Valid();
        $v->disableFeedback();

        $r1 = new MockRule($this);
        $r1->setReturnValue('isValid', true);
        $v->addRule($r1);

        $r2 = new MockRule($this);
        $r2->setReturnValue('isValid', false);
        $v->addRule($r2);

        $r3 = new MockRule($this);
        $r3->setReturnValue('isValid', true);
        $v->addRule($r3);

        $this->assertFalse($v->validate('value'));
    }

    // All conditions are tested
    function testAllRulesChecked()
    {
        $v = new Valid();
        $v->disableFeedback();

        $r1 = new MockRule($this);
        $r1->setReturnValue('isValid', true);
        $r1->expectOnce('isValid');
        $v->addRule($r1);

        $r2 = new MockRule($this);
        $r2->setReturnValue('isValid', false);
        $r2->expectOnce('isValid');
        $v->addRule($r2);

        $r3 = new MockRule($this);
        $r3->setReturnValue('isValid', true);
        $r3->expectOnce('isValid');
        $v->addRule($r3);

        $v->validate('value');
    }


    function testDefaultErrorMessage()
    {
        $v = new Valid();
        $v->disableFeedback();

        $r = new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->expectOnce('getErrorMessage');
        $v->addRule($r);

        $v->validate('value');
    }

    function testNoErrorMessage()
    {
        $v = new Valid();
        $v->disableFeedback();

        $r = new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->expectNever('getErrorMessage');
        $v->addRule($r, 'warning', 'test');

        $v->validate('value');
    }

    function testNotRequiredEmptyCall()
    {
        $r1 = new MockRule($this);
        $r1->expectNever('isValid');
        $v1 = new Valid();
        $v1->disableFeedback();
        $v1->addRule($r1);
        $v1->validate('');

        $r2 = new MockRule($this);
        $r2->expectNever('isValid');
        $v2 = new Valid();
        $v2->addRule($r2);
        $v2->validate(false);

        $r3 = new MockRule($this);
        $v3 = new Valid();
        $r3->expectNever('isValid');
        $v3->addRule($r3);
        $v3->validate(null);
    }

    function testRequiredEmptyCall()
    {
        $r1 = new MockRule($this);
        $r1->expectOnce('isValid');
        $v1 = new Valid();
        $v1->disableFeedback();
        $v1->required();
        $v1->addRule($r1);
        $v1->validate('');

        $r2 = new MockRule($this);
        $r2->expectOnce('isValid');
        $v2 = new Valid();
        $v2->disableFeedback();
        $v2->required();
        $v2->addRule($r2);
        $v2->validate(false);

        $r3 = new MockRule($this);
        $r3->expectOnce('isValid');
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
    function testRequiredAndPermissive()
    {
        $r = new MockRule($this);
        $r->setReturnValue('isValid', true);

        $v = new Valid();
        $v->disableFeedback();
        $v->required();
        $v->addRule($r);
        $this->assertFalse($v->validate(''));
    }

    function testValueEmpty()
    {
        $v = new Valid();
        $this->assertTrue($v->isValueEmpty(''));
        $this->assertTrue($v->isValueEmpty(false));
        $this->assertTrue($v->isValueEmpty(null));
        $this->assertFalse($v->isValueEmpty(' '));
    }

    function testNoFeedback()
    {
        $v = new ValidTestVersion($this);
        $v->disableFeedback();
        $v->expectNever('addFeedback');

        $r = new MockRule($this);
        $r->setReturnValue('isValid', false);
        $v->addRule($r);

        $v->validate('value');
    }

    function testFeedback()
    {
        $v = new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->__construct();
        $v->expectOnce('addFeedback');

        $r = new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->setReturnValue('getErrorMessage', 'error');
        $v->addRule($r);

        $v->validate('value');
    }

    function testFeedbackErrorWhenRequired()
    {
        $v = new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->__construct();
        $v->required();
        $v->expectOnce('addFeedback', array('error', 'error message'));

        $r = new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->setReturnValue('getErrorMessage', 'error message');
        $v->addRule($r);

        $v->validate('value');
    }

    function testFeedbackWarning()
    {
        $v = new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->__construct();
        $v->expectOnce('addFeedback', array('warning', 'error message'));

        $r = new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->setReturnValue('getErrorMessage', 'error message');
        $v->addRule($r);

        $v->validate('value');
    }

    function testFeedbackGlobal()
    {
        $v = new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->__construct();
        $v->expectOnce('addFeedback', array('warning', 'custom message'));

        $v->setErrorMessage('custom message');

        // Built-in message
        $r1 = new MockRule($this);
        $r1->setReturnValue('isValid', false);
        $r1->setReturnValue('getErrorMessage', 'built-in error message');
        $v->addRule($r1);

        // Developer message
        $r2 = new MockRule($this);
        $r2->setReturnValue('isValid', false);
        $v->addRule($r2, 'Just in time message');

        $v->validate('value');
    }

    function testFeedbackGlobalWithoutErrors()
    {
        $v = new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->__construct();
        $v->expectNever('addFeedback');

        $v->setErrorMessage('custom message');

        // Built-in message
        $r1 = new MockRule($this);
        $r1->setReturnValue('isValid', true);
        $r1->setReturnValue('getErrorMessage', 'built-in error message');
        $v->addRule($r1);

        $v->validate('value');
    }
}
