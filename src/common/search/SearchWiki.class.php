<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Search_SearchWiki
{
    public const NAME = 'wiki';

    public const SEARCH_PAGENAME_EN = 'FullTextSearch';
    public const SEARCH_PAGENAME_FR = 'RechercheEnTexteIntégral';

    /**
     * @var WikiDao
     */
    private $dao;


    public function __construct(WikiDao $dao)
    {
        $this->dao = $dao;
    }

    public function search(Search_SearchQuery $query)
    {
        if ($query->getProject()->isError()) {
            return;
        }

        $project_id = $query->getProject()->getId();
        $GLOBALS['Response']->redirect($this->getRedirectUrl(
            $project_id,
            $this->getSearchPageName($project_id),
            $query->getWords()
        ));
    }

    public function getRedirectUrl($project_id, $page_name, $words)
    {
        return '/wiki/index.php?group_id=' . $project_id . '&pagename=' . $page_name . '&s=' . urlencode($words);
    }

    public function getSearchPageName($project_id)
    {
        $search_page = self::SEARCH_PAGENAME_EN;
        if ($this->dao->searchLanguage($project_id) == 'fr_FR') {
            $search_page = self::SEARCH_PAGENAME_FR;
        }

        return $search_page;
    }

    public function getFacets($project_id, $words = '')
    {
        return new Search_SearchTypePresenter(
            Search_SearchWiki::NAME,
            $GLOBALS['Language']->getText('project_admin_editservice', 'service_wiki_lbl_key'),
            [],
            $this->getRedirectUrl($project_id, $this->getSearchPageName($project_id), $words)
        );
    }
}
