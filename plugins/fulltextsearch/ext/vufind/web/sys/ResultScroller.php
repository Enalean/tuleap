<?php
/**
 *
 * Copyright (C) Villanova University 2010
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

require_once 'services/MyResearch/lib/Search.php';

class ResultScroller {
    protected $enabled;

    /**
     * Constructor. Create a new search result scroller. 
     */
    public function __construct()
    {
        global $configArray;

        // Is this functionality enabled in config.ini?
        $this->enabled = (isset($configArray['Record']) && 
            isset($configArray['Record']['next_prev_navigation']) &&
            $configArray['Record']['next_prev_navigation']);
    }

    /**
     * Initialize this result set scroller. This should only be called 
     * prior to displaying the results of a new search.
     * 
     * @param $searchObject The search object that was used to execute the last search.
     * @param $result       The result of the last search.
     * 
     * @access public
     * @return boolean
     */
    public function init($searchObject, $result)
    {
        // Do nothing if disabled:
        if (!$this->enabled) {
            return;
        }

        // Save the saved search ID of this search in the session
        $_SESSION['lastSearchId'] = $searchObject->getSearchId();

        // Save the current page number to the session 
        $_SESSION['currentPageNumber'] = $searchObject->getPage();
        
        // Save the last search limit to the session
        $_SESSION['lastSearchLimit'] = $searchObject->getLimit();
        
        // Save the total number of results to the session
        $_SESSION['lastSearchResultTotal'] = $searchObject->getResultTotal();

        // save the IDs of records on the current page to the session
        // so we can "slide" from one record to the next/previous records
        // spanning 2 consecutive pages
        $recordIDs = array();
        foreach ($result['response']['docs'] as $record) {
            $recordIDs[] = $record['id'];
        }
        $_SESSION['recordsOnCurrentPage'] = $recordIDs;

        // clear the previous/next page
        unset($_SESSION['recordsOnPreviousPage']);
        unset($_SESSION['recordsOnNextPage']);

        return true;
    }

    /**
     * Get the previous/next record in the last search
     * result set relative to the current one, also return
     * the position of the current record in the result set.
     * Return array('previousRecord'=>previd, 'nextRecord'=>nextid, 
     * 'currentPosition'=>number, 'resultTotal'=>number).
     *
     * @param $id
     * @return array
     */
    public function getScrollData($id)
    {
        $retVal = array(
            'previousRecord'=>null,
            'nextRecord'=>null, 
            'currentPosition'=>null,
            'resultTotal'=>null);

        // Do nothing if disabled:
        if (!$this->enabled) {
            return $retVal;
        }

        if (isset($_SESSION['recordsOnCurrentPage']) && isset($_SESSION['lastSearchId'])) {
            // we need to restore the last search object
            // to fetch either the previous/next page of results
            $lastSearch = $this->restoreLastSearch();

            // give up if we can not restore the last search
            if (!$lastSearch) {
                return $retVal;
            }

            $currentPageNumber = $_SESSION['currentPageNumber'];
            $recordsOnCurrentPage = $_SESSION['recordsOnCurrentPage'];
            $recordsOnPreviousPage = isset($_SESSION['recordsOnPreviousPage']) ? $_SESSION['recordsOnPreviousPage'] : null;
            $recordsOnNextPage = isset($_SESSION['recordsOnNextPage']) ? $_SESSION['recordsOnNextPage'] : null;
            $resultTotal = isset($_SESSION['lastSearchResultTotal']) ? $_SESSION['lastSearchResultTotal'] : 0;
            $retVal['resultTotal'] = $resultTotal;

            // find where this record is in the current result page
            $pos = array_search($id, $recordsOnCurrentPage);
            if ($pos !== false) {
                // OK, found this record in the current result page
                // calculate it's position relative to the result set
                $retVal['currentPosition'] = ($currentPageNumber - 1) * $_SESSION['lastSearchLimit'] + $pos + 1;

                // count how many records in the current result page
                $count = count($recordsOnCurrentPage);

                // if the current record is somewhere in the middle
                // of the current page, ie: not first or last
                // then it is easy
                if ($pos > 0 && $pos < $count - 1) {
                    $retVal['previousRecord'] = $recordsOnCurrentPage[$pos - 1];
                    $retVal['nextRecord'] = $recordsOnCurrentPage[$pos + 1];
                    // and we're done
                    return $retVal;
                }

                // if this record is first record on the current page
                if ($pos == 0) {
                    // if the current page is NOT the first page, and
                    // the previous page has not been fetched before, then
                    // fetch the previous page
                    if ($currentPageNumber > 1 && $recordsOnPreviousPage == null) {
                        $recordsOnPreviousPage = $this->fetchPage($lastSearch, $currentPageNumber - 1);
                        $_SESSION['recordsOnPreviousPage'] = $recordsOnPreviousPage;
                    }

                    // if there is something on the previous page, then the previous
                    // record is the last record on the previous page
                    if (!empty($recordsOnPreviousPage)) {
                        $retVal['previousRecord'] = $recordsOnPreviousPage[count($recordsOnPreviousPage) - 1];
                    }

                    // if it is not the last record on the current page, then
                    // we also have a next record on the current page
                    if ($pos < $count - 1) {
                        $retVal['nextRecord'] = $recordsOnCurrentPage[$pos + 1];
                    }

                    // and we're done
                    return $retVal;
                }

                // if this record is last record on the current page
                if ($pos == $count - 1) {
                    // if the next page has not been fetched, then
                    // fetch the next page
                    if ($recordsOnNextPage == null) {
                        $recordsOnNextPage = $this->fetchPage($lastSearch, $currentPageNumber + 1);
                        $_SESSION['recordsOnNextPage'] = $recordsOnNextPage;
                    }

                    // if there is something on the next page, then the next
                    // record is the first record on the next page
                    if (!empty($recordsOnNextPage)) {
                        $retVal['nextRecord'] = $recordsOnNextPage[0];
                    }

                    // if it is not the first record on the current page, then
                    // we also have a previous record on the current page
                    if ($pos > 0) {
                        $retVal['previousRecord'] = $recordsOnCurrentPage[$pos - 1];
                    }

                    // and we're done
                    return $retVal;
                }
            } else {
                // the current record is not on the current page

                // if there is something on the previous page
                if (!empty($recordsOnPreviousPage)) {
                    // check if current record is on the previous page
                    $pos = array_search($id, $recordsOnPreviousPage);
                    if ($pos !== false) {
                        // decrease the currentPageNumber in the session because we're now
                        // sliding into the previous page
                        $currentPageNumber--;
                        $_SESSION['currentPageNumber'] = $currentPageNumber;

                        // save current page
                        $tmp = $recordsOnCurrentPage;

                        // the previous page becomes the current page
                        $recordsOnCurrentPage = $recordsOnPreviousPage;
                        $_SESSION['recordsOnCurrentPage'] = $recordsOnCurrentPage;

                        // the old current page becomes the new next page
                        $recordsOnNextPage = $tmp;
                        $_SESSION['recordsOnNextPage'] = $recordsOnNextPage;

                        // destroy the old previous page
                        $recordsOnPreviousPage = null;
                        $_SESSION['recordsOnPreviousPage'] = $recordsOnPreviousPage;

                        // now we can set the previous/next record
                        if ($pos > 0) {
                            $retVal['previousRecord'] = $recordsOnCurrentPage[$pos - 1];
                        }
                        $retVal['nextRecord'] = $recordsOnNextPage[0];

                        // recalculate the current position
                        $retVal['currentPosition'] = ($currentPageNumber - 1) * $_SESSION['lastSearchLimit'] + $pos + 1;

                        // update the search URL in the session
                        $lastSearch->setPage($currentPageNumber);
                        $_SESSION['lastSearchURL'] = $lastSearch->renderSearchUrl();

                        // and we're done
                        return $retVal;
                    }
                }

                // if there is something on the next page
                if (!empty($recordsOnNextPage)) {
                    // check if current record is on the next page
                    $pos = array_search($id, $recordsOnNextPage);
                    if ($pos !== false) {
                        // increase the currentPageNumber in the session because we're now
                        // sliding into the next page
                        $currentPageNumber++;
                        $_SESSION['currentPageNumber'] = $currentPageNumber;

                        // save the current page
                        $tmp = $recordsOnCurrentPage;
                        
                        // the next page becomes the current page
                        $recordsOnCurrentPage = $recordsOnNextPage;
                        $_SESSION['recordsOnCurrentPage'] = $recordsOnCurrentPage;

                        // the old current page becomes the new previous page
                        $recordsOnPreviousPage = $tmp;
                        $_SESSION['recordsOnPreviousPage'] = $recordsOnPreviousPage;

                        // destroy the old next page
                        $recordsOnNextPage = null;
                        $_SESSION['recordsOnNextPage'] = $recordsOnNextPage;

                        // now we can set the previous/next record
                        $retVal['previousRecord'] = $recordsOnPreviousPage[count($recordsOnPreviousPage) - 1];
                        if ($pos < count($recordsOnCurrentPage) - 1) {
                            $retVal['nextRecord'] = $recordsOnCurrentPage[$pos + 1];
                        }

                        // recalculate the current position
                        $retVal['currentPosition'] = ($currentPageNumber - 1) * $_SESSION['lastSearchLimit'] + $pos + 1;

                        // update the search URL in the session
                        $lastSearch->setPage($currentPageNumber);
                        $_SESSION['lastSearchURL'] = $lastSearch->renderSearchUrl();

                        // and we're done
                        return $retVal;
                    }
                }
            }
        }
        return $retVal;
    }


    /**
     * Fetch the given page of results from the given search object and
     * return the IDs of the records in an array.
     *
     * @param $searchObject The search object to be used to execute the search
     * @param $page         The page number to fetch
     * @return array
     */
    private function fetchPage($searchObject, $page) {
        $searchObject->setPage($page);
        $result = $searchObject->processSearch(true, false);
        if (PEAR::isError($result)) {
            PEAR::raiseError($result->getMessage());
        }
        $retVal = array();
        foreach ($result['response']['docs'] as $record) {
            $retVal[] = $record['id'];
        }
        return $retVal;
    }

    /**
     * Restore the last saved search.
     * @return SearchObject
     */
    private function restoreLastSearch()
    {
        if (isset($_SESSION['lastSearchId'])) {
            $search = new SearchEntry();
            $search->id = $_SESSION['lastSearchId'];
            if ($search->find(true)) {
                $minSO = unserialize($search->search_object);
                $savedSearch = SearchObjectFactory::deminify($minSO);
                return $savedSearch;
            }
        }
        return null;
    }
}

?>
