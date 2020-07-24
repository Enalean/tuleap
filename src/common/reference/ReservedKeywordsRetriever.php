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

namespace Tuleap\reference;

use Event;

class ReservedKeywordsRetriever
{
    /**
     * @var \EventManager
     */
    private $event_manager;

    public function __construct(\EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    private function getReservedKeyWords()
    {
        return [
            "art",
            "artifact",
            "doc",
            "file",
            "wiki",
            "cvs",
            "svn",
            "news",
            "forum",
            "msg",
            "cc",
            "tracker",
            "release",
            "tag",
            "thread",
            "im",
            "project",
            "folder",
            "plugin",
            "img",
            "commit",
            "rev",
            "revision",
            "patch",
            "proj",
            "dossier"
        ];
    }

    public function loadReservedKeywords()
    {
        $additional_reserved_keywords = [];

        $this->event_manager->processEvent(
            Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES,
            ['keywords' => &$additional_reserved_keywords]
        );

        return array_merge($this->getReservedKeyWords(), $additional_reserved_keywords);
    }
}
