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

use BaseLanguage;
use BaseLanguageFactory;
use ForgeConfig;

class UgroupLabelConverter
{
    /**
     * @var ListFieldBindValueNormalizer
     */
    private $bind_value_normalizer;
    /**
     * @var BaseLanguageFactory
     */
    private $base_language_factory;
    /**
     * @var array
     */
    private $index;

    public function __construct(ListFieldBindValueNormalizer $bind_value_normalizer, $base_language_factory)
    {
        $this->bind_value_normalizer = $bind_value_normalizer;
        $this->base_language_factory = $base_language_factory;

        $this->index = array();
        foreach ($this->getAvailableLanguages() as $language) {
            $base_language = $this->base_language_factory->getBaseLanguage($language);

            $long_label                = $this->getNormalizedTranslatedLabel('ugroup_project_members', $base_language);
            $this->index[$long_label]  = 'ugroup_project_members_name_key';

            $long_label                = $this->getNormalizedTranslatedLabel('ugroup_project_admins', $base_language);
            $this->index[$long_label]  = 'ugroup_project_admins_name_key';
        }
    }

    private function getNormalizedTranslatedLabel(
        $ugroup_label_translation_key,
        BaseLanguage $base_language
    ) {
        $translated_label = $base_language->getText('project_ugroup', $ugroup_label_translation_key);
        return $this->bind_value_normalizer->normalize($translated_label);
    }

    public function isASupportedDynamicUgroup($ugroup_translated_label)
    {
        $normalized_label = $this->bind_value_normalizer->normalize($ugroup_translated_label);

        return (array_key_exists($normalized_label, $this->index) === true);
    }

    public function convertLabelToTranslationKey($ugroup_translated_label)
    {
        $normalized_label = $this->bind_value_normalizer->normalize($ugroup_translated_label);

        $index_value = $this->index[$normalized_label];
        if (! $index_value) {
            return $ugroup_translated_label;
        }

        return $index_value;
    }

    private function getAvailableLanguages()
    {
        return explode(',', ForgeConfig::get('sys_supported_languages'));
    }
}
