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

class MediawikiAdminLanguagePanePresenter extends MediawikiAdminPanePresenter
{

    /** @var array */
    private $supported_languages;

    public function __construct(Project $project, array $supported_languages)
    {
        parent::__construct($project);
        $this->supported_languages = $supported_languages;
    }

    public function available_languages_title()
    {
        return $GLOBALS['Language']->getText('plugin_mediawiki', 'available_languages_title');
    }

    public function available_languages_intro()
    {
        return $GLOBALS['Language']->getText('plugin_mediawiki', 'available_languages_intro');
    }

    public function route()
    {
        return MEDIAWIKI_BASE_URL . '/forge_admin.php?' . http_build_query(array(
            'group_id' => $this->project->getID(),
            'action'   => 'save_language'
        ));
    }

    public function available_languages()
    {
        return $this->supported_languages;
    }

    public function save_changes()
    {
        return $GLOBALS['Language']->getText('plugin_mediawiki', 'save_label');
    }
}
