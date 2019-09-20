<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class ConfigTestWhiteBoxVersion extends ForgeConfig
{

    public static function load(ConfigValueProvider $value_provider)
    {
        return parent::load($value_provider);
    }
}

class ConfigTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testUsage()
    {
        $this->assertFalse(ForgeConfig::get('toto'));
        ForgeConfig::loadFromFile(dirname(__FILE__).'/_fixtures/config/local.inc');
        $this->assertEqual(ForgeConfig::get('toto'), 66);
        $this->assertFalse(ForgeConfig::get('titi')); //not defined should return false
    }

    public function testDefault()
    {
        $this->assertEqual(ForgeConfig::get('toto', 99), 99); //not defined should return default value given in parameter
        ForgeConfig::loadFromFile(dirname(__FILE__).'/_fixtures/config/local.inc');
        $this->assertEqual(ForgeConfig::get('toto', 99), 66); //now it is defined. Should NOT return default value given in parameter
    }

    public function testMultipleFiles()
    {
        // Unitialized
        $this->assertIdentical(ForgeConfig::get('toto'), false);
        $this->assertIdentical(ForgeConfig::get('tutu'), false);
        $this->assertIdentical(ForgeConfig::get('tata'), false);

        // Load the first file
        ForgeConfig::loadFromFile(dirname(__FILE__).'/_fixtures/config/local.inc');
        $this->assertIdentical(ForgeConfig::get('toto'), 66);
        $this->assertIdentical(ForgeConfig::get('tutu'), 123);
        $this->assertIdentical(ForgeConfig::get('tata'), false);

        // Load the second one. Merge of the conf
        ForgeConfig::loadFromFile(dirname(__FILE__).'/_fixtures/config/other_file.inc.dist');
        $this->assertIdentical(ForgeConfig::get('toto'), 66);
        $this->assertIdentical(ForgeConfig::get('tutu'), 421);
        $this->assertIdentical(ForgeConfig::get('tata'), 456);
    }

    public function testDump()
    {
        ForgeConfig::loadFromFile(dirname(__FILE__).'/_fixtures/config/local.inc');
        ob_start();
        ForgeConfig::dump();
        $dump = ob_get_contents();
        ob_end_clean();
        $this->assertEqual($dump, var_export(array('toto' => 66, 'tutu' => 123), 1));
    }

    public function itDoesntEmitAnyNoticesOrWarningsWhenThereAreTwoRestoresAndOneLoad()
    {
        ForgeConfig::restore();
        ForgeConfig::restore();
        ForgeConfig::loadFromFile(dirname(__FILE__).'/_fixtures/config/local.inc');
    }

    public function itLoadsFromDatabase()
    {
        $dao = mock('ConfigDao');
        stub($dao)->searchAll()->returnsDar(array('name' => 'a_var', 'value' => 'its_value'));
        ConfigTestWhiteBoxVersion::load(new ConfigValueDatabaseProvider($dao));

        $this->assertEqual('its_value', ForgeConfig::get('a_var'));
    }
}

class ForgeConfig_areAnonymousAllowedTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }
    public function itReturnsTrueIfAccessModeIsAnonymous()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->assertTrue(ForgeConfig::areAnonymousAllowed());
    }

    public function itReturnsFalseIfAccessModeIsRegular()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->assertFalse(ForgeConfig::areAnonymousAllowed());
    }

    public function itReturnsFalseIfAccessModeIsRestricted()
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->assertFalse(ForgeConfig::areAnonymousAllowed());
    }
}

class ForgeConfig_getSuperPublicProjectsTest extends TuleapTestCase
{

    private $fixtures;
    private $default;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();

        $GLOBALS['Language'] = new MockBaseLanguage();

        $this->customised_file  = dirname(__FILE__) .'/../ForgeConfig/_fixtures/restricted_user_permissions.txt';
        $this->default_file = dirname(__FILE__) .'/../../../../site-content/en_US/include/restricted_user_permissions.txt';
    }

    public function tearDown()
    {
        unset($GLOBALS['Language']);
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itReturnsEmptyArrayIfRestrictedUserFileIsTheDefaultOne()
    {
        stub($GLOBALS['Language'])
            ->getContent('include/restricted_user_permissions', 'en_US')
            ->returns($this->default_file);

        $this->assertEqual(ForgeConfig::getSuperPublicProjectsFromRestrictedFile(), array());
    }

    public function itReturnsArrayOfProjectIdsDefinedInRestrictedUserFile()
    {
        stub($GLOBALS['Language'])
            ->getContent('include/restricted_user_permissions', 'en_US')
            ->returns($this->customised_file);

        $this->assertEqual(ForgeConfig::getSuperPublicProjectsFromRestrictedFile(), array(123, 456));
    }

    public function itDoesNotStorePublicProjectsInTheStorage()
    {
        stub($GLOBALS['Language'])
            ->getContent('include/restricted_user_permissions', 'en_US')
            ->returns($this->customised_file);

        ForgeConfig::getSuperPublicProjectsFromRestrictedFile();

        $this->assertIdentical(ForgeConfig::get('public_projects'), false);
    }
}
