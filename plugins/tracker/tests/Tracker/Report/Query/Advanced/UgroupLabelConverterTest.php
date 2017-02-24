<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
use TuleapTestCase;

class UgroupLabelConverterTest extends TuleapTestCase
{
    /** @var  UgroupLabelConverter */
    private $ugroup_label_converter;
    /** @var  \BaseLanguageFactory */
    private $base_language_factory;
    /** @var  \BaseLanguage */
    private $english_base_language;
    /** @var  \BaseLanguage */
    private $french_base_language;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();

        ForgeConfig::set('sys_supported_languages', 'en_US,fr_FR');

        $this->english_base_language = mock('\BaseLanguage');
        $this->french_base_language  = mock('\BaseLanguage');
        $this->base_language_factory = mock('\BaseLanguageFactory');
        stub($this->base_language_factory)->getBaseLanguage('en_US')->returns($this->english_base_language);
        stub($this->base_language_factory)->getBaseLanguage('fr_FR')->returns($this->french_base_language);

        stub($this->english_base_language)->getText(
            'project_ugroup',
            'ugroup_project_members_name_key'
        )->returns('project_members');
        stub($this->english_base_language)->getText(
            'project_ugroup',
            'ugroup_project_members'
        )->returns('Project members');
        stub($this->french_base_language)->getText(
            'project_ugroup',
            'ugroup_project_members_name_key'
        )->returns('membres_projet');
        stub($this->french_base_language)->getText(
            'project_ugroup',
            'ugroup_project_members'
        )->returns('Membres du projet');
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    private function initUgroupLabelConverter()
    {
        $this->ugroup_label_converter = new UgroupLabelConverter(
            new ListFieldBindValueNormalizer(),
            $this->base_language_factory
        );
    }

    public function itSupportsProjectMembersLabels()
    {
        $this->initUgroupLabelConverter();

        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Project MEMBERS'));
        $this->assertTrue($this->ugroup_label_converter->isASupportedDynamicUgroup('Membres du PROJET'));
    }

    public function itReturnsTheUgroupNameTranslationKeyForEnglishLongLabel()
    {
        $result = $this->ugroup_label_converter->convertLabelToTranslationKey('Project MEMBERS');

        $this->assertEqual($result, 'ugroup_project_members_name_key');
    }

    public function itReturnsTheUgroupNameTranslationKeyForFrenchLongLabel()
    {
        $result = $this->ugroup_label_converter->convertLabelToTranslationKey('Membres du PROJET');

        $this->assertEqual($result, 'ugroup_project_members_name_key');
    }
}
