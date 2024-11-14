<?php
/**
 * Copyright (c) Enalean, 2017 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced;

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Test\LegacyTabTranslationsSupport;

final class UgroupLabelConverterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use LegacyTabTranslationsSupport;

    /** @var  UgroupLabelConverter */
    private $ugroup_label_converter;
    /** @var  \BaseLanguageFactory */
    private $base_language_factory;
    /** @var  \BaseLanguage */
    private $english_base_language;
    /** @var  \BaseLanguage */
    private $french_base_language;

    protected function setUp(): void
    {
        $this->english_base_language = \Mockery::spy(\BaseLanguage::class);
        $this->french_base_language  = \Mockery::spy(\BaseLanguage::class);
        $this->base_language_factory = \Mockery::spy(\BaseLanguageFactory::class);
        $this->base_language_factory->shouldReceive('getBaseLanguage')->with('en_US')->andReturns($this->english_base_language);
        $this->base_language_factory->shouldReceive('getBaseLanguage')->with('fr_FR')->andReturns($this->french_base_language);

        $this->english_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_project_members')->andReturns('Project members');
        $this->english_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_project_admins')->andReturns('Project administrators');
        $this->english_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_authenticated_users')->andReturns('Registered and restricted users');
        $this->english_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_registered_users')->andReturns('Registered users');
        $this->english_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_file_manager_admin_name_key')->andReturns('file_manager_admins');
        $this->english_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_wiki_admin_name_key')->andReturns('wiki_admins');

        $this->french_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_project_members')->andReturns('Membres du projet');
        $this->french_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_project_admins')->andReturns('Administrateurs du projet');
        $this->french_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_authenticated_users')->andReturns('Utilisateurs enregistrés + restreints');
        $this->french_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_registered_users')->andReturns('Utilisateurs enregistrés');
        $this->french_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_file_manager_admin_name_key')->andReturns('admins_gestionnaire_fichier');
        $this->french_base_language->shouldReceive('getText')->with('project_ugroup', 'ugroup_wiki_admin_name_key')->andReturns('admins_wiki');
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();
    }

    private function initUGroupLabelConverter(): void
    {
        $this->ugroup_label_converter = new UgroupLabelConverter(
            new ListFieldBindValueNormalizer(),
            $this->base_language_factory
        );
    }

    public function testItSupportsAllDynamicUserGroupsLabels(): void
    {
        $this->initUGroupLabelConverter();
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Project MEMBERS'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Membres du PROJET'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Project ADMINISTRATORS'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Administrateurs du PROJET'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('REGISTERED and restricted users'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Utilisateurs enregistrés + RESTREINTS'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Registered Users'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Utilisateurs Enregistrés'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('File_manager_admins'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Admins_gestionnaire_fichier'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Wiki_admins'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Admins_wiki'));
    }

    public function testItReturnsTheUgroupNameTranslationKeyForEnglishLongLabel(): void
    {
        $this->initUGroupLabelConverter();
        $result = $this->ugroup_label_converter->convertLabelToTranslationKey('Project MEMBERS');

        $this->assertEquals('ugroup_project_members_name_key', $result);
    }

    public function testItReturnsTheUgroupNameTranslationKeyForFrenchLongLabel(): void
    {
        $this->initUGroupLabelConverter();
        $result = $this->ugroup_label_converter->convertLabelToTranslationKey('Membres du PROJET');

        $this->assertEquals('ugroup_project_members_name_key', $result);
    }

    public function testItReturnsTheUgroupNameTranslationKeyForCustomizedAuthenticatedUsersLabel(): void
    {
        ForgeConfig::set('ugroup_authenticated_label', 'Les Faux');
        $this->initUGroupLabelConverter();

        $result = $this->ugroup_label_converter->convertLabelToTranslationKey('Les FAUX');

        $this->assertEquals('ugroup_authenticated_users_name_key', $result);
    }

    public function testItReturnsTheUgroupNameTranslationKeyForCustomizedRegisteredUsersLabel(): void
    {
        ForgeConfig::set('ugroup_registered_label', 'Les Vrais');
        $this->initUGroupLabelConverter();

        $result = $this->ugroup_label_converter->convertLabelToTranslationKey('les VRAIS');

        $this->assertEquals('ugroup_registered_users_name_key', $result);
    }
}
