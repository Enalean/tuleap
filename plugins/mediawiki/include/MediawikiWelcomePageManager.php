<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

require_once 'AlternativePagePresenter.php';

class MediawikiWelcomePageManager {

    private static $WELCOME_PAGES = array(
        'en_US' => 'Main_Page',
        'fr_FR' => 'Accueil'
    );

    /** @var MediawikiLanguageManager */
    private $language_manager;

    public function __construct(MediawikiLanguageManager $language_manager) {
        $this->language_manager = $language_manager;
    }

    public function displayWelcomePage(Project $project, HTTPRequest $request) {
        if (! $this->getLanguageForProject($project)) {
            $this->displayAlternativeWelcomePage($project, $request);
            $this->exterminate();
        }
    }

    private function getLanguageForProject(Project $project) {
        $used_language = $this->language_manager->getUsedLanguageForProject($project);

        if ($used_language) {
            return $used_language;
        }

        $languages = $this->language_manager->getAvailableLanguages();

        if (count($languages) == 1) {
            $this->language_manager->saveLanguageOption($project, $languages[0]);
            return $languages[0];
        }

        return;
    }

    private function displayAlternativeWelcomePage(Project $project, HTTPRequest $request) {
        $service = $project->getService(MediaWikiPlugin::SERVICE_SHORTNAME);
        $alternative_page_presenter = new Mediawiki_AlternativePagePresenter(
            $this->getAvailableLanguagesWithWelcomePages($project),
            $service->userIsAdmin($request),
            $this->getUrlForLanguageAdmin($project)
        );

        $service->renderInPage(
            $request,
            $GLOBALS['Language']->getText('plugin_mediawiki', 'alternative_welcome_page_title'),
            'alternative-welcome-page',
            $alternative_page_presenter
        );
    }

    private function getAvailableLanguagesWithWelcomePages(Project $project) {
        $available_languages         = $this->language_manager->getAvailableLanguages();
        $languages_with_welcome_page = array();

        foreach ($available_languages as $available_language) {
            if (array_key_exists($available_language, self::$WELCOME_PAGES)) {
                $languages_with_welcome_page[] = array(
                    'page_name' => self::$WELCOME_PAGES[$available_language],
                    'url'       => $this->getUrlForWikiPage($project, self::$WELCOME_PAGES[$available_language])
                );
            }
        }

        return $languages_with_welcome_page;
    }

    private function getUrlForLanguageAdmin(Project $project) {
        return MEDIAWIKI_BASE_URL.'/forge_admin?group_id='.$project->getID().'&pane=language';
    }

    private function getUrlForWikiPage(Project $project, $page_name) {
        return MEDIAWIKI_BASE_URL.'/wiki/'.$project->getUnixName().'/index.php/'.$page_name;
    }

    // This is for testing purpose
    protected function exterminate() {
        exit;
    }
}
