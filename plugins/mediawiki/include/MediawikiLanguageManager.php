<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All rights reserved
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class MediawikiLanguageManager
{

    /** @var MediawikiLanguageDao */
    private $dao;

    public function __construct(MediawikiLanguageDao $dao)
    {
        $this->dao = $dao;
    }

    public function saveLanguageOption(Project $project, $language)
    {
        if (! $language) {
            return;
        }

        if (! $this->isLanguageSupported($language)) {
            throw new Mediawiki_UnsupportedLanguageException($language);
        }

        return $this->dao->updateLanguageOption($project->getID(), $language);
    }

    /**
     *
     * @return string
     */
    public function getUsedLanguageForProject(Project $project)
    {
        $language = null;
        $result   = $this->dao->getUsedLanguageForProject($project->getID());

        if (isset($result['language'])) {
            $language = $result['language'];
        } else {
            $available_languages = $this->getAvailableLanguages();
            if (count($available_languages) === 1) {
                $language = $available_languages[0];
                $this->saveLanguageOption($project, $language);
            }
        }

        return $language;
    }

    public function getAvailableLanguagesWithUsage(Project $project)
    {
        $available_languages = $this->getAvailableLanguages();
        $project_language    = $this->getUsedLanguageForProject($project);

        return $this->formatAvailableLanguagesWithUsage($available_languages, $project_language);
    }

    private function formatAvailableLanguagesWithUsage(array $available_languages, $project_language)
    {
        $formatted_available_languages = array();

        foreach ($available_languages as $available_language) {
            $formatted_available_languages[] = array(
                'language' => $available_language,
                'used'     => $available_language == $project_language
            );
        }

        return $formatted_available_languages;
    }

    private function getAvailableLanguages()
    {
        return explode(',', ForgeConfig::get('sys_supported_languages'));
    }

    private function isLanguageSupported($language)
    {
        $supported_languages = $this->getAvailableLanguages();

        return in_array($language, $supported_languages);
    }
}
