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

use BaseLanguage;
use BaseLanguageFactory;
use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\LegacyTabTranslationsSupport;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UgroupLabelConverterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use LegacyTabTranslationsSupport;

    private UgroupLabelConverter $ugroup_label_converter;
    private BaseLanguageFactory&MockObject $base_language_factory;
    private BaseLanguage&MockObject $english_base_language;
    private BaseLanguage&MockObject $french_base_language;

    #[\Override]
    protected function setUp(): void
    {
        $this->english_base_language = $this->createMock(\BaseLanguage::class);
        $this->french_base_language  = $this->createMock(\BaseLanguage::class);
        $this->base_language_factory = $this->createMock(\BaseLanguageFactory::class);
        $this->base_language_factory->method('getBaseLanguage')
            ->willReturnCallback(fn (string $locale) => match ($locale) {
                'en_US' => $this->english_base_language,
                'fr_FR' => $this->french_base_language,
            });

        $this->english_base_language->method('getText')
            ->willReturnCallback(static fn (string $pagename, string $category) => match ($category) {
                'ugroup_project_members' => 'Project members',
                'ugroup_project_admins' => 'Project administrators',
                'ugroup_authenticated_users' => 'Registered and restricted users',
                'ugroup_registered_users' => 'Registered users',
                'ugroup_file_manager_admin_name_key' => 'file_manager_admins',
                'ugroup_wiki_admin_name_key' => 'wiki_admins',
            });

        $this->french_base_language->method('getText')
            ->willReturnCallback(static fn (string $pagename, string $category) => match ($category) {
                'project_ugroup', 'ugroup_project_members' => 'Membres du projet',
                'ugroup_project_admins' => 'Administrateurs du projet',
                'ugroup_authenticated_users' => 'Utilisateurs enregistrés + restreints',
                'ugroup_registered_users' => 'Utilisateurs enregistrés',
                'ugroup_file_manager_admin_name_key' => 'admins_gestionnaire_fichier',
                'ugroup_wiki_admin_name_key' => 'admins_wiki',
            });
    }

    #[\Override]
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
