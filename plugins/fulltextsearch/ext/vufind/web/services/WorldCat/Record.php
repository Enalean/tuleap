<?php
/**
 *
 * Copyright (C) Andrew Nagy 2008.
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

require_once 'sys/Worldcat.php';
require_once 'sys/WorldCatUtils.php';
require_once 'sys/Language.php';
require_once 'sys/ISBN.php';

require_once 'services/MyResearch/lib/User.php';
require_once 'services/MyResearch/lib/Resource.php';
require_once 'services/MyResearch/lib/Resource_tags.php';
require_once 'services/MyResearch/lib/Tags.php';

class Record extends Base
{
    protected $id;
    protected $record;
    protected $isbn;
    protected $cacheId;
    protected $worldcat;

    function __construct()
    {
        global $configArray;
        global $interface;

        parent::__construct();

        // Assign the ID of the last search so the user can return to it.
        $interface->assign('lastsearch', isset($_SESSION['lastSearchURL']) ? 
            $_SESSION['lastSearchURL'] : false);

        $this->id = $_GET['id'];
        $interface->assign('id', $this->id);

        $this->cacheId = 'WCRecord|' . $this->id . '|' . get_class($this);

        // Define Default Tab
        $tab = (isset($_GET['action']) && $_GET['action'] != 'Record') ? 
            $_GET['action'] : 'Holdings';
        $interface->assign('tab', $tab);

        // Fetch Record
        $this->worldcat = new Worldcat();
        $record = $this->worldcat->getRecord($_GET['id']);
        if (PEAR::isError($record)) {
            PEAR::raiseError($record);
        }

        // Process MARCXML Data
        $marc = new File_MARCXML($record, File_MARC::SOURCE_STRING);
        if ($this->record = $marc->next()) {
            $interface->assign('marc', $this->record);
        } else {
            PEAR::raiseError('Cannot Process MARC Record');
        }

        // Save best available ISBN value:
        $this->isbn = $this->getBestISBN();

        // Define External Content Provider
        if ($this->isbn) {
            $interface->assign('isbn', $this->isbn);

            if (isset($configArray['Content']['reviews'])) {
                $interface->assign('hasReviews', true);
            }
            if (isset($configArray['Content']['excerpts'])) {
                $interface->assign('hasExcerpt', true);
            }
        }

        // Retrieve tags associated with the record
        $resource = new Resource();
        $resource->record_id = $_GET['id'];
        $resource->source = 'WorldCat';
        $tags = $resource->getTags();
        $interface->assign('tagList', is_array($tags) ? $tags : array());

        // Find Similar Records
        $similar = $this->worldcat->getMoreLikeThis($this->record);
        $interface->assign('similarRecords', $similar['record']);

        // Find Other Editions
        $editions = $this->getEditions();
        if (!PEAR::isError($editions)) {
            $interface->assign('editions', $editions);
        }

        // Define CoINs Identifier
        $coinsID = isset($configArray['OpenURL']['rfr_id']) ?
            $configArray['OpenURL']['rfr_id'] : 
            $configArray['COinS']['identifier'];
        if (empty($coinsID)) {
            $coinsID = 'vufind.svn.sourceforge.net';
        }
        $interface->assign('coinsID', $coinsID);
      
        // Set Proxy URL
        $interface->assign('proxy', isset($configArray['EZproxy']['host']) ?
            $configArray['EZproxy']['host'] : false);
    }
    
    function launch()
    {
        require_once 'Holdings.php';
        Holdings::launch();
    }
    
    private function getBestISBN()
    {
        // Get ISBN for cover and review use
        $isbn13 = false;
        if ($isbnFields = $this->record->getFields('020')) {
            if (is_array($isbnFields)) {
                foreach($isbnFields as $isbnField) {
                    if ($isbnSubField = $isbnField->getSubfield('a')) {
                        $isbn = trim($isbnSubField->getData());
                        if ($pos = strpos($this->isbn, ' ')) {
                            $isbn = substr($this->isbn, 0, $pos);
                        }
                        // If we find an ISBN-10, return it immediately; otherwise, if we find
                        // an ISBN-13, save it if it is the first one encountered.
                        $isbnObj = new ISBN($isbn);
                        if ($isbn10 = $isbnObj->get10()) {
                            return $isbn10;
                        }
                        if (!$isbn13) {
                            $isbn13 = $isbnObj->get13();
                        }
                    }
                }
            }
        }
        return $isbn13;
    }

    function getEditions()
    {
        $wc = new WorldCatUtils();

        // Try to build an array of ISBN or ISSN-based sub-queries:
        $query = '';
        if (!empty($this->isbn)) {
            $isbnList = $wc->getXISBN($this->isbn);
            if (!empty($isbnList)) {
                $query = 'srw.bn any "' . implode(' ', $isbnList) . '"';
            }
        } else if ($issnField = $this->record->getField('022')) {
            if ($issnData = $issnField->getSubfield('a')) {
                $issnList = $wc->getXISSN(trim($issnData->getData()));
                if (!empty($issnList)) {
                    $query = 'srw.sn any "' . implode(' ', $issnList) . '"';
                }
            }
        }

        // If we have query parts, we should try to find related records:
        if (!empty($query)) {
            // Assemble the query parts and filter out current record:
            $query = '(' . $query . ') not srw.no all "' . $this->id . '"';

            // Perform the search and return either results or an error:
            $result = $this->worldcat->search($query, null, 1, 5, 'LibraryCount,,0');
            if (!PEAR::isError($result)) {
                return isset($result['record']) ? $result['record'] : null;
            } else {
                return $result;
            }
        }

        // If we got this far, we were unable to find any results:
        return null;
    }
}

?>