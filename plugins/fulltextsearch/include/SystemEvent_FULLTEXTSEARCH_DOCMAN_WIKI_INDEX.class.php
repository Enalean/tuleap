<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\PHPWiki\WikiPage;


class SystemEvent_FULLTEXTSEARCH_DOCMAN_WIKI_INDEX extends SystemEvent_FULLTEXTSEARCH_DOCMAN {
    const NAME = 'FULLTEXTSEARCH_DOCMAN_WIKI_INDEX';

    protected function processItem(Docman_Item $item) {
        $project_id = $item->getGroupId();

        if (! $this->actions->checkProjectMappingExists($project_id)) {
            $this->actions->initializeProjetMapping($project_id);
        }

        $wiki_page = $this->getWikiPage($item, $project_id);

        $this->actions->indexNewWikiDocument($item, $wiki_page->getMetadata());

        return true;
    }

    /**
     * @return WikiPage
     */
    protected function getWikiPage(Docman_Item $item, $project_id) {
        return new WikiPage($project_id, $item->getPagename());
    }
}