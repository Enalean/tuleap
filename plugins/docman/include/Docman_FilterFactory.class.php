<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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

class Docman_FilterFactory
{
    public $dynTextFields;
    public $groupId;

    public function __construct($groupId)
    {
        $this->dynTextFields = array();
        $this->groupId = $groupId;
    }

    public function addFiltersToReport(&$report)
    {
        $gsMd = $this->getGlobalSearchMetadata();
        $globalSearch = false;
        $gsRow = null;

        $itsMd = $this->getItemTypeSearchMetadata();
        $itemTypeSearchSearch = false;
        $itsRows = array();

        $filtersArray = array();

        $metadataFactory = new Docman_MetadataFactory($report->getGroupId());
        $dao = $this->getDao();
        $dar = $dao->searchByReportId($report->getId());
        if ($dar && !$dar->isError()) {
            while ($dar->valid()) {
                $row = $dar->current();
                if ($row['label'] == $gsMd->getLabel()) {
                    $gsRow = $row;
                    $globalSearch = true;
                } elseif ($row['label'] == $itsMd->getLabel()) {
                    $itsRows[] = $row;
                    $itemTypeSearchSearch = true;
                } else {
                    if (isset($filtersArray[$row['label']])) {
                        $f = $filtersArray[$row['label']];
                        $f->initFromRow($row);
                    } else {
                        $md = $metadataFactory->getFromLabel($row['label']);
                        $f = $this->createFromMetadata($md, $report->getAdvancedSearch());
                        $f->initFromRow($row);
                        $filtersArray[$row['label']] = $f;
                    }
                    unset($f);
                }

                $dar->next();
            }
        }
        // Build the report.
        // In order to build the report in the same order than build from
        // url, we have to use the MD order returned by getMetadataForGroup
        // method of metadataFactory: first the hardcoded md like defined in
        // the class and then the real metadata ordered by label ASC.
        foreach (Docman_MetadataFactory::HARDCODED_METADATA_LABELS as $mdLabel) {
            if (isset($filtersArray[$mdLabel])) {
                $report->addFilter($filtersArray[$mdLabel]);
                unset($filtersArray[$mdLabel]);
            }
        }
        // Then loop on the real md.
        foreach ($filtersArray as $f) {
            $report->addFilter($f);
            unset($f);
        }

        // Add global search always at the end
        if ($globalSearch) {
            $f = new Docman_FilterGlobalText($gsMd, $this->dynTextFields);
            $f->initFromRow($gsRow);
            $report->addFilter($f);
            unset($f);
        }

        if ($itemTypeSearchSearch) {
            $f = $this->createItemTypeFilter($itsMd, $report->getAdvancedSearch());

            foreach ($itsRows as $itsRow) {
                $f->initFromRow($itsRow);
            }

            $report->addFilter($f);
            unset($f);
        }
    }

    public function getGlobalSearchMetadata()
    {
        // Special case for a fake metadata: generic text search
        $md = new Docman_Metadata();
        $md->setGroupId($this->groupId);
        $md->setName(dgettext('tuleap-docman', 'Global text search'));
        $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);
        $md->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
        $md->setLabel('global_txt');
        return $md;
    }

    public function getItemTypeSearchMetadata()
    {
        // Special case for a fake metadata: item type search
        $md = new Docman_ListMetadata();
        $md->setGroupId($this->groupId);
        $md->setName(dgettext('tuleap-docman', 'Item type'));
        $md->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);
        $md->setUseIt(PLUGIN_DOCMAN_METADATA_USED);
        $md->setLabel('item_type');
        $md->setIsMultipleValuesAllowed(true);

        $row = array();
        $values = array();
        $item_factory = Docman_ItemFactory::instance($this->groupId);
        foreach (array('file', 'wiki', 'embeddedfile', 'empty', 'link', 'folder') as $type) {
            $row['value_id'] = constant('PLUGIN_DOCMAN_ITEM_TYPE_' . strtoupper($type));
            $row['name'] = $item_factory->getItemTypeAsText($type);
            $row['status'] = 'A';
            $love = new Docman_MetadataListOfValuesElement();
            $love->initFromRow($row);
            $values[] = $love;
        }

        $md->setListOfValueElements($values);

        return $md;
    }

    /**
     * Fake filter only used to display the global text search as a default
     * option when no filter selected.
     */
    public function getFakeGlobalSearchFilter()
    {
        $md = $this->getGlobalSearchMetadata();
        $filter = new Docman_FilterGlobalText($md, '');
        return $filter;
    }

    public function getGlobalSearchFilter($request)
    {
        $md = $this->getGlobalSearchMetadata();

        // set-up Filter
        $filter = new Docman_FilterGlobalText($md, $this->dynTextFields);
        return $this->_initFilter($filter, $request);
    }

    public function getItemTypeSearchFilter($request, $advSearch)
    {
        $md = $this->getItemTypeSearchMetadata();

        // set-up Filter
        if ($advSearch) {
            $filter = new Docman_FilterItemTypeAdvanced($md);
        } else {
            $filter = new Docman_FilterItemType($md);
        }

        return $this->_initFilter($filter, $request);
    }

    public function createFilterOnMatch($md, $request, $advSearch)
    {
        $f = $this->createFromMetadata($md, $advSearch);
        return $this->_initFilter($f, $request);
    }

    public function createFromMetadata($md, $advSearch)
    {
        $filter = null;

        if ($md->getLabel() == 'owner') {
            $filter = new Docman_FilterOwner($md);
        } else {
            switch ($md->getType()) {
                case PLUGIN_DOCMAN_METADATA_TYPE_TEXT:
                case PLUGIN_DOCMAN_METADATA_TYPE_STRING:
                    $filter = new Docman_FilterText($md);
                    if (Docman_MetadataFactory::isRealMetadata($md->getLabel())) {
                        $this->dynTextFields[] = $md->getLabel();
                    }
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_DATE:
                    if ($advSearch) {
                        $filter = new Docman_FilterDateAdvanced($md);
                    } else {
                        $filter = new Docman_FilterDate($md);
                    }
                    break;
                case PLUGIN_DOCMAN_METADATA_TYPE_LIST:
                    if ($advSearch) {
                        $filter = new Docman_FilterListAdvanced($md);
                    } else {
                        $filter = new Docman_FilterList($md);
                    }
                    break;
            }
        }

        return $filter;
    }

    public function createItemTypeFilter($md, $advSearch)
    {
        if ($advSearch) {
            $f = new Docman_FilterItemTypeAdvanced($md);
        } else {
            $f = new Docman_FilterItemType($md);
        }

        return $f;
    }

    public function _initFilter($filter, $request)
    {
        if ($filter !== null) {
            if ($filter->initOnUrlMatch($request)) {
                return $filter;
            }
        }
        return null;
    }

    public function createFiltersFromReport($report)
    {
        $fi = $report->getFilterIterator();
        while ($fi->valid()) {
            $filter = $fi->current();
            $this->createFilter($report->getId(), $filter);
            $fi->next();
        }
    }

    public function createFilter($reportId, $filter)
    {
        $dao = $this->getDao();

        if (is_a($filter, 'Docman_FilterDateAdvanced')) {
            $dao->createFilterDateAdvanced($reportId, $filter->md->getLabel(), $filter->getValueStart(), $filter->getValueEnd());
        } elseif (is_a($filter, 'Docman_FilterDate')) {
            $dao->createFilterDate($reportId, $filter->md->getLabel(), $filter->getValue(), $filter->getOperator());
        } elseif (is_a($filter, 'Docman_FilterListAdvanced')) {
            foreach ($filter->getValue() as $val) {
                $dao->createFilterList($reportId, $filter->md->getLabel(), $val);
            }
        } elseif (is_a($filter, 'Docman_FilterList')) {
            $dao->createFilterList($reportId, $filter->md->getLabel(), $filter->getValue());
        } else {
            $dao->createFilterText($reportId, $filter->md->getLabel(), $filter->getValue());
        }
    }

    /**
     * Delete all the filters of the given report.
     */
    public function truncateFilters($report)
    {
        $dao = $this->getDao();
        return $dao->truncateFilters($report->getId());
    }

    /**
     * Clone a list of filters from a report ($srcReport) to another one
     * ($dstReport) according to mapping between metadata ($metadataMapping).
     *
     * This function can be use in two context:
     * - docman instanciation (creation of docman) in this case there is a
     * complete equivalence between the 2 projects (same metadata and values in
     * both projects).
     * - report import. In this case, there is probably strong differences
     * between metadata of the 2 projects. This function tries to work at best
     * and create in the $dstReport all the matching possibilities (either
     * metadata or values).
     */
    public function copy($srcReport, $dstReport, $metadataMapping)
    {
        $this->addFiltersToReport($srcReport);

        $fi = $srcReport->getFilterIterator();
        $fi->rewind();
        while ($fi->valid()) {
            $srcFilter = $fi->current();
            $this->cloneFilter($srcFilter, $dstReport, $metadataMapping);
            $fi->next();
        }
    }

    /**
     * Wrapper to obtain an instance of Docman_FilterFactory
     * Technically useless but for unit tests
     *
     * @param $groupId id of the project
     *
     * @retunr Docman_FilterFactory
     */
    public function getFilterFactory($groupId)
    {
        return new Docman_FilterFactory($groupId);
    }

    public function cloneFilter($srcFilter, $dstReport, $metadataMapping)
    {
        $dstMdFactory = new Docman_MetadataFactory($dstReport->getGroupId());

        $newLabel = null;
        if ($dstMdFactory->isRealMetadata($srcFilter->md->getLabel())) {
            // Check if there is a corresponding MD in the dst project
            // Should never happens in case of initial template clone
            // but main exists with 'clone this report' function
            if (isset($metadataMapping['md'][$srcFilter->md->getId()])) {
                // For real metadata, create MD based on the new ID
                $newLabel = 'field_' . $metadataMapping['md'][$srcFilter->md->getId()];
            }
        } else {
            // Check in use
            $newLabel = $srcFilter->md->getLabel();
        }

        if ($newLabel !== null) {
            $dstFilterFactory = $this->getFilterFactory($dstReport->getGroupId());

            $gsMd = $this->getGlobalSearchMetadata();
            if ($newLabel == $gsMd->getLabel()) {
                $newMd = $gsMd;
            } else {
                $itMd = $this->getItemTypeSearchMetadata();
                if ($newLabel == $itMd->getLabel()) {
                    $newMd = $itMd;
                } else {
                    $newMd = $dstMdFactory->getFromLabel($newLabel);
                }
            }

            if ($newMd->isUsed()) {
                // Create new filter
                $dstFilter = $dstFilterFactory->createFromMetadata($newMd, $dstReport->getAdvancedSearch());

                // Append values
                $this->cloneFilterValues($srcFilter, $dstFilter, $metadataMapping);

                // Save filter
                $dstFilterFactory->createFilter($dstReport->getId(), $dstFilter);
            }
        }
    }

    public function cloneFilterValues($srcFilter, &$dstFilter, $metadataMapping)
    {
        $dstVal = null;

        if (is_a($srcFilter, 'Docman_FilterDateAdvanced')) {
            $dstFilter->setValueStart($srcFilter->getValueStart());
            $dstFilter->setValueEnd($srcFilter->getValueEnd());
        } elseif (is_a($srcFilter, 'Docman_FilterDate')) {
            $dstFilter->setValue($srcFilter->getValue());
            $dstFilter->setOperator($srcFilter->getOperator());
        } elseif (is_a($srcFilter, 'Docman_FilterListAdvanced')) {
            $dstVal = array();
            foreach ($srcFilter->getValue() as $val) {
                $v = $this->getLoveClonedValue($srcFilter, $val, $metadataMapping);
                if ($v !== null) {
                    $dstVal[] = $v;
                }
            }
        } elseif (is_a($srcFilter, 'Docman_FilterList')) {
            $dstVal = $this->getLoveClonedValue($srcFilter, $srcFilter->getValue(), $metadataMapping);
        } else {
            $dstVal = $srcFilter->getValue();
        }

        if ($dstVal !== null) {
            $dstFilter->setValue($dstVal);
        }
    }

    public function getLoveClonedValue($srcFilter, $value, $metadataMapping)
    {
        $dstVal = null;

        if ($srcFilter->md->getLabel() == 'status' || $srcFilter->md->getLabel() == 'item_type') {
            $dstVal = $value;
        } elseif (isset($metadataMapping['love'][$value])) {
            $dstVal = $metadataMapping['love'][$value];
        }

        return $dstVal;
    }

    public function &getDao()
    {
        $dao = new Docman_FilterDao(CodendiDataAccess::instance());
        return $dao;
    }
}
