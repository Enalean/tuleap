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

require_once 'sys/Language.php';

require_once 'RecordDrivers/Factory.php';
require_once 'sys/ResultScroller.php';

class Record extends Action
{
    protected $recordDriver;
    protected $cacheId;
    protected $db;
    
    function __construct()
    {
        global $configArray;
        global $interface;
        
        //$interface->caching = 1;

        // Define Default Tab
        $defaultTab = isset($configArray['Site']['defaultRecordTab']) ?
            $configArray['Site']['defaultRecordTab'] : 'Holdings';
        $tab = (isset($_GET['action'])) ? $_GET['action'] : $defaultTab;
        $interface->assign('tab', $tab);

        // Store ID of current record (this is needed to generate appropriate
        // links, and it is independent of which record driver gets used).
        $interface->assign('id', $_GET['id']);

        // Setup Search Engine Connection
        $class = $configArray['Index']['engine'];
        $url = $configArray['Index']['url'];
        $this->db = new $class($url);
        if ($configArray['System']['debug']) {
            $this->db->debug = true;
        }

        // Retrieve the record from the index
        if (!($record = $this->db->getRecord($_GET['id']))) {
            PEAR::raiseError(new PEAR_Error('Record Does Not Exist'));
        }
        $this->recordDriver = RecordDriverFactory::initRecordDriver($record);

        if ($this->recordDriver->hasRDF()) {
            $interface->assign('addHeader', '<link rel="alternate" ' .
                'type="application/rdf+xml" title="RDF Representation" href="' . 
                $configArray['Site']['url']  . '/Record/' . urlencode($_GET['id']) .
                '/RDF">');
        }
        $interface->assign('coreMetadata', $this->recordDriver->getCoreMetadata());

        // Set flags that control which tabs are displayed:
        if (isset($configArray['Content']['reviews'])) {
            $interface->assign('hasReviews', $this->recordDriver->hasReviews());
        }
        if (isset($configArray['Content']['excerpts'])) {
            $interface->assign('hasExcerpt', $this->recordDriver->hasExcerpt());
        }
        $interface->assign('hasTOC', $this->recordDriver->hasTOC());

        // Assign the next/previous record data:
        $scroller = new ResultScroller();
        $scrollData = $scroller->getScrollData($_GET['id']);
        $interface->assign('previousRecord', $scrollData['previousRecord']);
        $interface->assign('nextRecord', $scrollData['nextRecord']);
        $interface->assign('currentRecordPosition', $scrollData['currentPosition']);
        $interface->assign('resultTotal', $scrollData['resultTotal']);

        // Retrieve User Search History
        $interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ? 
            $_SESSION['lastSearchURL'] : false);

        $this->cacheId = 'Record|' . $_GET['id'] . '|' . get_class($this);
        if (!$interface->is_cached($this->cacheId)) {
            // Find Similar Records
            $similar = $this->db->getMoreLikeThis($_GET['id']);
            
            // Send the similar items to the template; if there is only one, we need
            // to force it to be an array or things will not display correctly.
            if (count($similar['response']['docs']) > 0) {
                $interface->assign('similarRecords', $similar['response']['docs']);
            }
            
            // Find Other Editions
            $editions = $this->recordDriver->getEditions();
            if (!PEAR::isError($editions)) {
                $interface->assign('editions', $editions);
            }
        }

        // Send down text for inclusion in breadcrumbs
        $interface->assign('breadcrumbText', $this->recordDriver->getBreadcrumb());

        // Send down OpenURL for COinS use:
        $interface->assign('openURL', $this->recordDriver->getOpenURL());
        
        // Send down legal export formats (if any):
        $interface->assign('exportFormats', $this->recordDriver->getExportFormats());
        
        // Set AddThis User
        $interface->assign('addThis', isset($configArray['AddThis']['key']) ?
            $configArray['AddThis']['key'] : false);

        // Set Proxy URL
        if (isset($configArray['EZproxy']['host'])) {
            $interface->assign('proxy', $configArray['EZproxy']['host']);
        }
    }

    /**
     * Record a record hit to the statistics index when stat tracking is enabled;
     * this is called by the Home action.
     */
    public function recordHit()
    {
        global $configArray;

        if ($configArray['Statistics']['enabled']) {
            // Setup Statistics Index Connection
            $solrStats = new SolrStats($configArray['Statistics']['solr']);
            if ($configArray['System']['debug']) {
                $solrStats->debug = true;
            }

            // Save Record View
            $solrStats->saveRecordView($this->recordDriver->getUniqueID());
            unset($solrStats);
        }
    }
}

?>
