<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';

require_once 'sys/Pager.php';

class Search extends Action
{
    function launch()
    {
        global $interface;

        $interface->caching = false;

        // Retrieve User Search History
        $interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ? 
            $_SESSION['lastSearchURL'] : false);

        // Initialise from the current search globals
        $searchObject = SearchObjectFactory::initSearchObject();
        $searchObject->init();
// TODO : Stats

        $result = $searchObject->processSearch();
        if (PEAR::isError($result)) {
            PEAR::raiseError($result->getMessage());
        }

        $interface->assign('sortList',    $searchObject->getSortList());
        $interface->assign('lookfor',     $searchObject->displayQuery());
        $interface->assign('searchType',  $searchObject->getSearchType());
        $interface->assign('searchIndex', $searchObject->getSearchIndex());
        $interface->assign('qtime',       round($searchObject->getQuerySpeed(), 2));

        $summary = $searchObject->getResultSummary();
        // Post processing, remember that the REAL results here with regards to
        //   numbers and pagination are the author facet, not the document
        //   results from the solr index. So access '$summary' with care.

        $page = $summary['page'];
        $limit = $summary['perPage'];

        // The search object will have returned an array of author facets that
        // is offset to display the current page of results but which has more
        // than a single page worth of content.  This allows a user to dig deeper
        // and deeper into the result set even though we have no way of finding
        // out the exact count of results without risking a memory overflow or
        // long delay.  We need to use the current page information to adjust the
        // known total count accordingly, and we need to use the page size to
        // crop off extra results when displaying the list to the user.
        // See VUFIND-127 in JIRA for more details.
        $authors = $result['facet_counts']['facet_fields']['authorStr'];
        $cnt = (($page - 1) * $limit) + count($authors);
        $interface->assign('recordSet', array_slice($authors, 0, $limit));

        $interface->assign('recordCount', $cnt);
        $interface->assign('recordStart', (($page - 1) * $limit) + 1);
        if (($cnt < $limit) || ($page * $limit) > $cnt) {
            $interface->assign('recordEnd', $cnt);
        } else {
            $interface->assign('recordEnd', $page * $limit);
        }

        $link = $searchObject->renderLinkPageTemplate();
        $options = array('totalItems' => $cnt, 'fileName' => $link);
        $pager = new VuFindPager($options);
        $interface->assign('pageLinks', $pager->getLinks());

        $interface->setPageTitle('Author Browse');
        $interface->setTemplate('list.tpl');
        $interface->display('layout.tpl');
    }

}
?>