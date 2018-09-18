<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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

abstract class SystemEvent_FULLTEXTSEARCH_WIKI extends SystemEvent {

    /**
     * @var FullTextSearchWikiActions
     */
    protected $actions;

    public function injectDependencies(FullTextSearchWikiActions $actions) {
        $this->actions = $actions;
    }

    /**
     * Process the system event
     *
     * @return bool
     */
    public function process() {
        try {
            $group_id       = (int)$this->getRequiredParameter(0);
            $wiki_page_name = (string)$this->getRequiredParameter(1);

            $wiki_page = $this->getWikiPage($group_id, $wiki_page_name);

            if ($wiki_page) {
                if ($this->processItem($wiki_page, $group_id)) {
                    $this->done();
                    return true;
                }
            } else {
                $this->error('Wiki page not found');
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
        return false;
    }

    protected function getWikiPage($group_id, $wiki_page_name) {
        return new WikiPage($group_id, $wiki_page_name);
    }

    /**
     * Execute action on the given item
     *
     * @see process()
     *
     * @param WikiPage $wiki_page The wiki page
     *
     * @return bool true if success (means status=done), false otherwise
     */
    protected abstract function processItem(WikiPage $wiki_page, $group_id);

    /**
     * @return string a human readable representation of parameters
     */
    public function verbalizeParameters($with_link) {
        $group_id       = (int)$this->getRequiredParameter(0);
        $wiki_page_name = (string)$this->getRequiredParameter(1);

        $txt = 'project: '. $this->verbalizeProjectId($group_id, $with_link) .', wiki page: '. $this->verbalizeWikiPageId($group_id, $wiki_page_name, $with_link);

        return $txt;
    }

    private function verbalizeWikiPageId($group_id, $wiki_page_name, $with_link) {
        if ($with_link) {
            $txt = '<a href="/wiki/index.php?group_id='. $group_id .'&pagename='. $wiki_page_name.'">'. $wiki_page_name .'</a>';
        }
        return $txt;
    }
}
?>
