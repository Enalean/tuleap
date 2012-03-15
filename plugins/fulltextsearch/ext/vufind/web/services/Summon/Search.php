<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009.
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

require_once 'Base.php';

require_once 'sys/Pager.php';
require_once 'sys/Summon.php';

class Search extends Base
{
    function launch()
    {
        global $interface;
        global $configArray;

        // Initialise SearchObject.
        $this->searchObject->init();

        $interface->setPageTitle('Search Results');
        
        $interface->assign('lookfor', $this->searchObject->displayQuery());
        $interface->assign('searchIndex', $this->searchObject->getSearchIndex());
        $interface->assign('searchType', $this->searchObject->getSearchType());

        // Search Summon
        $result = $this->searchObject->processSearch(true, true);

        // We'll need recommendations no matter how many results we found:
        $interface->assign('qtime', round($this->searchObject->getQuerySpeed(), 2));
        $interface->assign('spellingSuggestions', $this->searchObject->getSpellingSuggestions());
        $interface->assign('topRecommendations',
            $this->searchObject->getRecommendationsTemplates('top'));
        $interface->assign('sideRecommendations',
            $this->searchObject->getRecommendationsTemplates('side'));

        if ($result['recordCount'] > 0) {
            // If the "jumpto" parameter is set, jump to the specified result index:
            $this->processJumpto($result);

            $summary = $this->searchObject->getResultSummary();
            $page = $summary['page'];
            $interface->assign('recordCount', $summary['resultTotal']);
            $interface->assign('recordStart', $summary['startRecord']);
            $interface->assign('recordEnd',   $summary['endRecord']);
            $interface->assign('recordSet', $result['documents']);
            $interface->assign('sortList',   $this->searchObject->getSortList());

            // If our result set is larger than the number of records that
            // Summon will let us page through, we should cut off the number
            // before passing it to our paging mechanism:
            $config = getExtraConfigArray('Summon');
            $pageLimit = isset($config['General']['result_limit']) ?
                $config['General']['result_limit'] : 2000;
            $totalPagerItems = $summary['resultTotal'] < $pageLimit ?
                $summary['resultTotal'] : $pageLimit;

            // Process Paging
            $link = $this->searchObject->renderLinkPageTemplate();
            $options = array('totalItems' => $totalPagerItems,
                             'fileName'   => $link,
                             'perPage'    => $summary['perPage']);
            $pager = new VuFindPager($options);
            $interface->assign('pageLinks', $pager->getLinks());

            // Display Listing of Results
            $interface->setTemplate('list.tpl');
            $interface->assign('subpage', 'Summon/list-list.tpl');
        } else {
            $interface->assign('recordCount', 0);
            // Was the empty result set due to an error?
            $error = $this->searchObject->getIndexError();
            if ($error !== false) {
                // If it's a parse error or the user specified an invalid field, we 
                // should display an appropriate message:
                if (stristr($error, 'user.entered.query.is.malformed') ||
                    stristr($error, 'unknown.field')) {
                    $interface->assign('parseError', true);
                    
                // Unexpected error -- let's treat this as a fatal condition.
                } else {
                    PEAR::raiseError(new PEAR_Error('Unable to process query<br />' .
                        'Summon Returned: ' . $error));
                }
            }
            $interface->setTemplate('list-none.tpl');
        }
        
        // 'Finish' the search... complete timers and log search history.
        $this->searchObject->close();
        $interface->assign('time', round($this->searchObject->getTotalSpeed(), 2));
        // Show the save/unsave code on screen
        // The ID won't exist until after the search has been put in the search history
        //    so this needs to occur after the close() on the searchObject
        $interface->assign('showSaved',   true);
        $interface->assign('savedSearch', $this->searchObject->isSavedSearch());
        $interface->assign('searchId',    $this->searchObject->getSearchId());
        
        // Save the URL of this search to the session so we can return to it easily:
        $_SESSION['lastSearchURL'] = $this->searchObject->renderSearchUrl();
        
        $interface->display('layout.tpl');
    }

    /**
     * Process the "jumpto" parameter.
     *
     * @access  private
     * @param   array       $result         Summon result
     */
    private function processJumpto($result)
    {
        if (isset($_REQUEST['jumpto']) && is_numeric($_REQUEST['jumpto'])) {
            $i = intval($_REQUEST['jumpto'] - 1);
            if (isset($result['documents'][$i])) {
                $record = & $result['documents'][$i];
                $jumpUrl = 'Record?id=' . urlencode($record['ID'][0]);
                header('Location: ' . $jumpUrl);
                die();
            }
        }
    }
}

?>
