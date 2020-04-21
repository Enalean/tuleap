<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Config;

use ConfigValueDatabaseProvider;
use ConfigValueProvider;
use ForgeAccess;
use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ForgeConfigTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::store();
        $GLOBALS['Language'] = \Mockery::mock(\BaseLanguage::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Language']);
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testUsage(): void
    {
        $this->assertFalse(ForgeConfig::get('toto'));
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertEquals(ForgeConfig::get('toto'), 66);
        $this->assertFalse(ForgeConfig::get('titi')); //not defined should return false
    }

    public function testDefault(): void
    {
        $this->assertEquals(
            ForgeConfig::get('toto', 99),
            99
        ); //not defined should return default value given in parameter
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertEquals(
            ForgeConfig::get('toto', 99),
            66
        ); //now it is defined. Should NOT return default value given in parameter
    }

    public function testMultipleFiles(): void
    {
        // Unitialized
        $this->assertSame(ForgeConfig::get('toto'), false);
        $this->assertSame(ForgeConfig::get('tutu'), false);
        $this->assertSame(ForgeConfig::get('tata'), false);

        // Load the first file
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertSame(ForgeConfig::get('toto'), 66);
        $this->assertSame(ForgeConfig::get('tutu'), 123);
        $this->assertSame(ForgeConfig::get('tata'), false);

        // Load the second one. Merge of the conf
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/other_file.inc.dist');
        $this->assertSame(ForgeConfig::get('toto'), 66);
        $this->assertSame(ForgeConfig::get('tutu'), 421);
        $this->assertSame(ForgeConfig::get('tata'), 456);
    }

    public function testDump(): void
    {
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        ob_start();
        ForgeConfig::dump();
        $dump = ob_get_clean();
        $this->assertEquals($dump, var_export(['toto' => 66, 'tutu' => 123], true));
    }

    public function testItDoesntEmitAnyNoticesOrWarningsWhenThereAreTwoRestoresAndOneLoad(): void
    {
        ForgeConfig::restore();
        ForgeConfig::restore();
        ForgeConfig::loadFromFile(__DIR__ . '/_fixtures/config/local.inc');
        $this->assertTrue(true);
    }

    public function testItLoadsFromDatabase(): void
    {
        $dao = \Mockery::mock(\ConfigDao::class);
        $dao->shouldReceive('searchAll')->andReturns([['name' => 'a_var', 'value' => 'its_value']]);
        (new class extends ForgeConfig {
            public static function load(ConfigValueProvider $value_provider): void
            {
                parent::load($value_provider);
            }
        })::load(new ConfigValueDatabaseProvider($dao));

        $this->assertEquals('its_value', ForgeConfig::get('a_var'));
    }

    public function testItReturnsTrueIfAccessModeIsAnonymous(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->assertTrue(ForgeConfig::areAnonymousAllowed());
    }

    public function testItReturnsFalseIfAccessModeIsRegular(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->assertFalse(ForgeConfig::areAnonymousAllowed());
    }

    public function testItReturnsFalseIfAccessModeIsRestricted(): void
    {
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->assertFalse(ForgeConfig::areAnonymousAllowed());
    }

    public function testItReturnsEmptyArrayIfRestrictedUserFileIsTheDefaultOne(): void
    {
        $default_file    = __DIR__ . '/../../../../site-content/en_US/include/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->shouldReceive('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->andReturns($default_file);

        $this->assertEquals(ForgeConfig::getSuperPublicProjectsFromRestrictedFile(), []);
    }

    public function testItReturnsArrayOfProjectIdsDefinedInRestrictedUserFile(): void
    {
        $customised_file = __DIR__ . '/_fixtures/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->shouldReceive('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->andReturns($customised_file);

        $this->assertEquals(ForgeConfig::getSuperPublicProjectsFromRestrictedFile(), [123, 456]);
    }

    public function testItDoesNotStorePublicProjectsInTheStorage(): void
    {
        $customised_file = __DIR__ . '/_fixtures/restricted_user_permissions.txt';

        $GLOBALS['Language']
            ->shouldReceive('getContent')
            ->with('include/restricted_user_permissions', 'en_US')
            ->andReturns($customised_file);

        ForgeConfig::getSuperPublicProjectsFromRestrictedFile();

        $this->assertFalse(ForgeConfig::get('public_projects'));
    }
}
