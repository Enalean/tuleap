<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('common/valid/Valid.class.php');

Mock::generate('Rule');
Mock::generatePartial('Valid', 'ValidTestVersion', array('addFeedback'));

class ValidTest extends UnitTestCase {

    function UnitTestCase($name = 'Valid test') {
        $this->UnitTestCase($name);
    }

    function testArgPropagate() {
        $v = new Valid();
        $v->disableFeedback();
        $r =& new MockRule($this);
        $r->expectOnce('isValid', array('value'));
        $v->addRuleRef($r);
        $v->validate('value');
        $r->tally();
    }

    function testRetPropagate() {
        $v = new Valid();
        $v->disableFeedback();
        $r =& new MockRule($this);
        $r->setReturnValue('isValid', true);
        $v->addRuleRef($r);
        $this->assertTrue($v->validate('value'));
    }

    // If one fails, all test fails
    function testOneFailAllFail() {
        $v = new Valid();
        $v->disableFeedback();

        $r1 =& new MockRule($this);
        $r1->setReturnValue('isValid', true);
        $v->addRuleRef($r1);

        $r2 =& new MockRule($this);
        $r2->setReturnValue('isValid', false);
        $v->addRuleRef($r2);

        $r3 =& new MockRule($this);
        $r3->setReturnValue('isValid', true);
        $v->addRuleRef($r3);

        $this->assertFalse($v->validate('value'));
    }

    // All conditions are tested
    function testAllRulesChecked() {
        $v = new Valid();
        $v->disableFeedback();

        $r1 =& new MockRule($this);
        $r1->setReturnValue('isValid', true);
        $r1->expectOnce('isValid');
        $v->addRuleRef($r1);

        $r2 =& new MockRule($this);
        $r2->setReturnValue('isValid', false);
        $r2->expectOnce('isValid');
        $v->addRuleRef($r2);

        $r3 =& new MockRule($this);
        $r3->setReturnValue('isValid', true);
        $r3->expectOnce('isValid');
        $v->addRuleRef($r3);

        $v->validate('value');
        $r1->tally();
        $r2->tally();
        $r3->tally();
    }


    function testDefaultErrorMessage() {
        $v = new Valid();
        $v->disableFeedback();

        $r =& new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->expectOnce('getErrorMessage');
        $v->addRuleRef($r);

        $v->validate('value');
        $r->tally();
    }

    function testNoErrorMessage() {
        $v = new Valid();
        $v->disableFeedback();

        $r =& new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->expectNever('getErrorMessage');
        $v->addRuleRef($r, 'warning', 'test');

        $v->validate('value');
    }

    function testNotRequiredEmptyCall() {
        $r1 =& new MockRule($this);
        $r1->expectNever('isValid');
        $v1 = new Valid();
        $v1->disableFeedback();
        $v1->addRuleRef($r1);
        $v1->validate('');

        $r2 =& new MockRule($this);
        $r2->expectNever('isValid');
        $v2 = new Valid();
        $v2->addRuleRef($r2);
        $v2->validate(false);

        $r3 =& new MockRule($this);
        $v3 = new Valid();
        $r3->expectNever('isValid');
        $v3->addRuleRef($r3);
        $v3->validate(null);
    }

    function testRequiredEmptyCall() {
        $r1 =& new MockRule($this);
        $r1->expectOnce('isValid');
        $v1 = new Valid();
        $v1->disableFeedback();
        $v1->required();
        $v1->addRuleRef($r1);
        $v1->validate('');

        $r2 =& new MockRule($this);
        $r2->expectOnce('isValid');
        $v2 = new Valid();
        $v2->disableFeedback();
        $v2->required();
        $v2->addRuleRef($r2);
        $v2->validate(false);

        $r3 =& new MockRule($this);
        $r3->expectOnce('isValid');
        $v3 = new Valid();
        $v3->disableFeedback();
        $v3->required();
        $v3->addRuleRef($r3);
        $v3->validate(null);

        $r1->tally();
        $r2->tally();
        $r3->tally();
    }

    function testNoFeedback() {
        $v =& new ValidTestVersion($this);
        $v->disableFeedback();
        $v->expectNever('addFeedback');

        $r =& new MockRule($this);
        $r->setReturnValue('isValid', false);
        $v->addRule($r);

        $v->validate('value');
    }

    function testFeedback() {
        $v =& new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->Valid();
        $v->expectOnce('addFeedback');

        $r =& new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->setReturnValue('getErrorMessage', 'error');
        $v->addRule($r);

        $v->validate('value');
        $v->tally();
    }

    function testFeedbackErrorWhenRequired() {
        $v =& new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->Valid();
        $v->required();
        $v->expectOnce('addFeedback', array('error', 'error message'));

        $r =& new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->setReturnValue('getErrorMessage', 'error message');
        $v->addRule($r);

        $v->validate('value');
        $v->tally();
    }

    function testFeedbackWarning() {
        $v =& new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->Valid();
        $v->expectOnce('addFeedback', array('warning', 'error message'));

        $r =& new MockRule($this);
        $r->setReturnValue('isValid', false);
        $r->setReturnValue('getErrorMessage', 'error message');
        $v->addRule($r);

        $v->validate('value');
        $v->tally();
    }

    function testFeedbackGlobal() {
        $v =& new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->Valid();
        $v->expectOnce('addFeedback', array('warning', 'custom message'));

        $v->setErrorMessage('custom message');

        // Built-in message
        $r1 =& new MockRule($this);
        $r1->setReturnValue('isValid', false);
        $r1->setReturnValue('getErrorMessage', 'built-in error message');
        $v->addRule($r1);

        // Developer message
        $r2 =& new MockRule($this);
        $r2->setReturnValue('isValid', false);
        $v->addRule($r2, 'Just in time message');

        $v->validate('value');
        $v->tally();
    }

    function testFeedbackGlobalWithoutErrors() {
        $v =& new ValidTestVersion($this);
        // Need to call the constructore manually
        $v->Valid();
        $v->expectNever('addFeedback');

        $v->setErrorMessage('custom message');

        // Built-in message
        $r1 =& new MockRule($this);
        $r1->setReturnValue('isValid', true);
        $r1->setReturnValue('getErrorMessage', 'built-in error message');
        $v->addRule($r1);

        $v->validate('value');
    }

}

?>
