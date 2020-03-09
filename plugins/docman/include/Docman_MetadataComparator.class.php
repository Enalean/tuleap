<?php
/*
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2007
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Docman_MetadataFactory.class.php');

class Docman_MetadataComparator
{
    public $docmanIcons;
    public $srcGo;
    public $dstGo;

    public function __construct($srcGroupId, $dstGroupId, $themePath)
    {
        $this->docmanIcons = new Docman_Icons($themePath.'/images/ic/');
        $pm = ProjectManager::instance();
        $this->srcGo = $pm->getProject($srcGroupId);
        $this->dstGo = $pm->getProject($dstGroupId);
    }

    /**
     * For a five object iterator, return an array of object indexed by
     * $func applied on object.
     */
    public function getArrayFromIterator($iter, $func)
    {
        $a = array();
        while ($iter->valid()) {
            $e = $iter->current();
            $a[$e->$func()] = $e;
            $iter->next();
        }
        return $a;
    }

    public function checkMdDifferences($srcMd, $dstMd, $loveMap)
    {
        $diffArray = array();
        if (!$dstMd->sameDescription($srcMd)) {
            $diffArray[] = $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_param_desc');
        }
        if (!$dstMd->sameIsEmptyAllowed($srcMd)) {
            $diffArray[] = $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_param_allowempty', $this->getEnabledDisabledText($srcMd->getIsEmptyAllowed()));
        }
        if (!$dstMd->sameIsMultipleValuesAllowed($srcMd)) {
            $diffArray[] = $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_param_allowmultiplevalue', $this->getEnabledDisabledText($srcMd->getIsMultipleValuesAllowed()));
        }
        if (!$dstMd->sameUseIt($srcMd)) {
            $diffArray[] = $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_param_useit', $this->getEnabledDisabledText($srcMd->getUseIt()));
        }
        return $diffArray;
    }

    private function getEnabledDisabledText(bool $is_enabled): string
    {
        if ($is_enabled) {
            return $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_param_1');
        }

        return $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_param_0');
    }

    /**
     *
     * Same algo used in Docman_View_ItemDetailsSectionPaste::_checkLoveToImport
     */
    public function getLoveCompareTable($srcMd, $dstMd, $mdMap, &$sthToImport)
    {
        $html = '';

        if ($srcMd->getLabel() == 'status') {
            // No differences possible with status.
            return $html;
        }

        // Get list of ListOfValues elements from dst project
        $srcLoveFactory = new Docman_MetadataListOfValuesElementFactory($srcMd->getId());
        $srcLoveIter = $srcLoveFactory->getIteratorByFieldId($srcMd->getId(), $srcMd->getLabel(), true);

        // Get list of ListOfValues elements from dst project
        $dstLoveFactory = new Docman_MetadataListOfValuesElementFactory($dstMd->getId());
        $dstLoveIter = $dstLoveFactory->getIteratorByFieldId($dstMd->getId(), $dstMd->getLabel(), true);
        $dstLoveArray = $this->getArrayFromIterator($dstLoveIter, 'getId');

        $purifier = Codendi_HTMLPurifier::instance();

        // Keep a trace of matching love
        $matchingLove = array();
        while ($srcLoveIter->valid()) {
            $srcLove = $srcLoveIter->current();
            $rowStyle = 'missing';

            // Compute the differences
            $dstLove = false;
            if (isset($mdMap['love'][$srcLove->getId()])) {
                $dstLove = $dstLoveArray[$mdMap['love'][$srcLove->getId()]];
                $matchingLove[$dstLove->getId()] = true;
                $rowStyle = 'equals';
            } else {
                $sthToImport = true;
            }

            $html .= "<tr>\n";

            // Name
            $html .= "<td style=\"padding-left: 2em;\"></td>\n";
            $html .= "<td>".Docman_MetadataHtmlList::_getElementName($srcLove)."</td>\n";

            // Presence in source project
            $html .= '<td align="center"><img src="'.$this->docmanIcons->getThemeIcon('tick.png').'" /></td>';

            // Presence in destination project
            $html .= "<td align=\"center\">";
            switch ($rowStyle) {
                case 'equals':
                    $html .= '<img src="'.$this->docmanIcons->getThemeIcon('tick.png').'" />';
                    break;
            }
            $html .= "</td>\n";

            // Differences
            $html .= "<td class=\"docman_md_".$rowStyle."\">";
            switch ($rowStyle) {
                case 'missing':
                    $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_status_missing');
            }
            $html .= "</td>\n";

            // Action
            $html .= "<td>";
            switch ($rowStyle) {
                case 'missing':
                    $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_act_import_love', array($purifier->purify($srcLove->getName())));
            }
            $html .= "</td\n>";

            $html .= "</tr>\n";

            $srcLoveIter->next();
        }

        // Append to the table the list of values elements in the dst project
        // that where not present in the src project.
        foreach ($dstLoveArray as $love) {
            if (!isset($matchingLove[$love->getId()])) {
                $html .= "<tr>\n";
                // Name
                $html .= "<td>&nbsp;</td>\n";
                $html .= "<td>".$purifier->purify($love->getName())."</td>\n";
                // Presence in source project
                $html .= "<td></td>\n";
                // Presence in destination project
                $html .= '<td align="center"><img src="'.$this->docmanIcons->getThemeIcon('tick.png').'" /></td>';
                // Differences
                $html .= "<td></td>\n";
                // Action
                $html .= "<td></td>\n";
                $html .= "</tr>\n";
            }
        }

        return $html;
    }

    public function getMetadataCompareTable(&$sthToImport)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html = '';

        // True if there is sth to import in dst project.
        $sthToImport = false;

        // For source project, only get the 'Used' metadata.
        $srcMdFactory = new Docman_MetadataFactory($this->srcGo->getGroupId());
        $srcMdIter = $srcMdFactory->getMetadataForGroup(true);

        // For destination (current) project, get all metadata.
        $dstMdFactory = new Docman_MetadataFactory($this->dstGo->getGroupId());
        $dstMdIter = $dstMdFactory->getMetadataForGroup();
        $dstMdArray = $this->getArrayFromIterator($dstMdIter, 'getLabel');

        // Get mapping between the 2 definitions
        $mdMap = array();
        $srcMdFactory->getMetadataMapping($this->dstGo->getGroupId(), $mdMap);

        $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_desc', array($purifier->purify($this->dstGo->getPublicName()), $purifier->purify($this->srcGo->getPublicName())));

        // Table
        $html .= "<table border=\"1\">\n";

        $html .= "<tr>\n";
        $html .= "<th colspan=\"2\">".$GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_prop')."</th>\n";
        $html .= "<th>".$purifier->purify($this->srcGo->getPublicName())."</th>\n";
        $html .= "<th>".$purifier->purify($this->dstGo->getPublicName())."</th>\n";
        $html .= "<th>".$GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_diff', array($purifier->purify($this->dstGo->getPublicName()), $purifier->purify($this->srcGo->getPublicName())))."</th>\n";
        $html .= "<th>".$GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_action', array($purifier->purify($this->dstGo->getPublicName())))."</th>\n";
        $html .= "</tr>\n";

        $purifier = Codendi_HTMLPurifier::instance();

        // Keep a trace of metadata that matched in the dst metadata list.
        $matchingMd = array();
        $srcMdIter->rewind();
        while ($srcMdIter->valid()) {
            $srcMd = $srcMdIter->current();
            $dstMd = null;

            // Compute the differences between the 2 projects
            $dstMdStatus = 'missing';
            $dstMdLabel = '';
            if ($srcMdFactory->isRealMetadata($srcMd->getLabel())) {
                if (isset($mdMap['md'][$srcMd->getId()])) {
                    $dstMdLabel = $srcMdFactory->getLabelFromId($mdMap['md'][$srcMd->getId()]);
                }
            } else {
                $dstMdLabel = $srcMd->getLabel();
            }

            if (isset($dstMdArray[$dstMdLabel])) {
                $dstMd = $dstMdArray[$dstMdLabel];
                if ($dstMd !== false) {
                    $matchingMd[$dstMdLabel] = true;
                    $dstMdStatus = 'equivalent';
                    if ($dstMd->equals($srcMd)) {
                        $dstMdStatus = 'equals';
                    } else {
                        $sthToImport = true;
                    }
                } else {
                    $sthToImport = true;
                }
            } else {
                // The metadata is not in the metadata map list, check if it's
                // not a name conflict
                $dstMdi = $dstMdFactory->findByName($srcMd->getName());
                if ($dstMdi->count() == 1) {
                    $dstMdStatus = 'conflict';
                } else {
                    $sthToImport = true;
                }
            }

            $purified_property_name = $purifier->purify($srcMd->getName());

            // Display result
            $html .= "<tr>\n";

            // Property
            $html .= "<td colspan=\"2\" style=\"font-weight: bold;\">";
            $html .= $purified_property_name;
            $html .= "</td>";

            // Presence in source project
            $html .= "<td align=\"center\">";
            $html .= '<img src="'.$this->docmanIcons->getThemeIcon('tick.png').'" />';
            $html .= "</td>";

            // Presence in destination project
            $html .= "<td align=\"center\">";
            switch ($dstMdStatus) {
                case 'equals':
                case 'equivalent':
                    $html .= '<img src="'.$this->docmanIcons->getThemeIcon('tick.png').'" />';
                    break;
            }
            $html .= "</td>";

            // Differences
            $html .= "<td class=\"docman_md_".$dstMdStatus."\">";
            switch ($dstMdStatus) {
                case 'equivalent':
                    $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_status_equivalent');
                    break;
                case 'missing':
                    $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_status_missing');
                    break;
                case 'conflict':
                    $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_status_conflict');
                    break;
            }
            $html .= "</td>";

            // Action
            $html .= "<td>";
            switch ($dstMdStatus) {
                case 'equals':
                    // Nothing to do
                    break;
                case 'equivalent':
                    $diffArray = $this->checkMdDifferences($srcMd, $dstMd, $mdMap['love']);
                    $diffStr = '<ul style="padding:0;padding-left:1.5em;margin:0;">';
                    foreach ($diffArray as $diff) {
                        $diff_purified = $purifier->purify($diff, CODENDI_PURIFIER_FULL);
                        $diffStr      .= "<li>$diff_purified</li>";
                    }
                    $diffStr .= '</ul>';

                    $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_act_update_md', array($purified_property_name, $purifier->purify($this->dstGo->getPublicName()), $diffStr));
                    break;
                case 'missing':
                    $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_act_import_md', array($purified_property_name));
                    break;
                case 'conflict':
                    $html .= $GLOBALS['Language']->getText('plugin_docman', 'admin_md_import_tbl_act_conflict');
                    break;
            }
            $html .= "</td>";

            $html .= "</tr>\n";

            // List of values
            if ($srcMd->getType() == PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
                if ($dstMd !== null) {
                    $html .= $this->getLoveCompareTable($srcMd, $dstMd, $mdMap, $sthToImport);
                }
            }

            unset($dstMd);
            $srcMdIter->next();
        }

        // Append to the table the metadata in the dst project that where not
        // present in the src project.
        foreach ($dstMdArray as $md) {
            if (!isset($matchingMd[$md->getLabel()])) {
                $html .= "<tr>\n";

                // Name
                $html .= "<td colspan=\"2\" style=\"font-weight: bold;\">";
                $purified_name = $purifier->purify($md->getName());
                $html .= $purified_name;
                $html .= "</td>";

                // Presence in source project
                $html .= "<td></td>";

                // Presence in destination project
                $html .= "<td align=\"center\">";
                $html .= '<img src="'.$this->docmanIcons->getThemeIcon('tick.png').'" />';
                $html .= "</td>";

                // Differences
                $html .= "<td></td>";

                // Action
                $html .= "<td></td>";

                $html .= "</td>";
                $html .= "</tr>\n";
            }
        }

        $html .= "</table>\n";

        return $html;
    }
}
