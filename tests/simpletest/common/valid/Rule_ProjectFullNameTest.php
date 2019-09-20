<?php
/**
 * Copyright (c) STMicroelectronics, 2011. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

Mock::generate('BaseLanguage');

class Rule_ProjectFullNameTest extends TuleapTestCase
{

    function __construct($name = 'Rule_ProjectFullNameFormat test')
    {
        parent::__construct($name);
    }

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['Language']->setReturnValue('getText', 'error_only_spaces', array('rule_group_name', 'error_only_spaces'));
        $GLOBALS['Language']->setReturnValue('getText', 'name_too_short', array('include_account','name_too_short'));
        $GLOBALS['Language']->setReturnValue('getText', 'name_too_long', array('include_account','name_too_long', 40));
    }

    function testIsValid()
    {
        $rule = new Rule_ProjectFullName();
        $this->assertTrue($rule->isValid("prj"));
        $this->assertEqual($rule->getErrorMessage(), null);
        $this->assertTrue($rule->isValid("       project name long by spaces       "));
        $this->assertEqual($rule->getErrorMessage(), null);

        $this->assertFalse($rule->isValid(""));
        $this->assertEqual($rule->getErrorMessage(), 'name_too_short');
        $this->assertFalse($rule->isValid(" "));
        $this->assertEqual($rule->getErrorMessage(), 'name_too_short');
        $this->assertFalse($rule->isValid("   "));
        $this->assertEqual($rule->getErrorMessage(), 'name_too_short');
        $this->assertFalse($rule->isValid("p"));
        $this->assertEqual($rule->getErrorMessage(), 'name_too_short');
        $this->assertFalse($rule->isValid("p   "));
        $this->assertEqual($rule->getErrorMessage(), 'name_too_short');
        $this->assertFalse($rule->isValid("pr"));
        $this->assertEqual($rule->getErrorMessage(), 'name_too_short');

        $this->assertFalse($rule->isValid("This a very very long project name longer than 40 characters :)"));
        $this->assertEqual($rule->getErrorMessage(), 'name_too_long');
    }
}
