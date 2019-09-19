<?php
/**
 * Copyright © Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Clément Plantier, 2009
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

require_once('Docman_View_ItemDetailsSection.class.php');
require_once('Docman_View_GetFieldsVisitor.class.php');

class Docman_View_ItemDetailsSectionStatistics extends Docman_View_ItemDetailsSection
{
    var $inheritableMetadataArray;
    var $_controller;

    function __construct($item, $url, $controller)
    {
        $this->_controller = $controller;

        $id = 'statistics';
        $title = $GLOBALS['Language']->getText('plugin_docman', 'details_statistics');
        parent::__construct($item, $url, $id, $title);
    }

    function _getPropertyRow($label, $value)
    {
        $html = '';
        $html .= '<tr style="vertical-align:top;">';
        $html .= '<td class="label">'.$label.'</td>';
        $html .= '<td class="value">'.$value.'</td>';
        $html .= '</tr>';
        return $html;
    }

    function _getStatisticsFields($params)
    {
        $html = '';

        if (is_a($this->item, 'Docman_Folder')) {
            $if = Docman_ItemFactory::instance($this->_controller->getGroupId());
            $stats = $if->getFolderStats($this->item, $this->_controller->getUser());

            $size =  $this->convertBytesToHumanReadable($stats['size']);

            // Summary
            $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_statistics_summary').'</h3>';
            $html .= '<table class="docman_item_details_properties">';
            $html .= $this->_getPropertyRow($GLOBALS['Language']->getText('plugin_docman', 'details_statistics_size'), $size);
            $html .= $this->_getPropertyRow($GLOBALS['Language']->getText('plugin_docman', 'details_statistics_children_count'), $stats['count']);
            $html .= '</table>';

            // Details
            if ($stats['count'] > 0) {
                $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_docman', 'details_statistics_details').'</h3>';
                $html .= '<p>'.$GLOBALS['Language']->getText('plugin_docman', 'details_statistics_details_description').'</p>';
                $html .= '<table class="docman_item_details_properties">';
                arsort($stats['types']);
                foreach ($stats['types'] as $type => $stat) {
                    $html .= $this->_getPropertyRow($GLOBALS['Language']->getText('plugin_docman', 'details_statistics_item_type_'.strtolower($type)), $stat);
                }
                $html .= '</table>';
            }
        }

        return $html;
    }

    function getContent($params = array())
    {
        $html = '<div class="docman_help">'.$GLOBALS['Language']->getText('plugin_docman', 'details_statistics_help').'</div>';
        $html .= $this->_getStatisticsFields($params);
        return $html;
    }

    private function convertBytesToHumanReadable($bytes)
    {
        $byteSymbol = $GLOBALS['Language']->getText('plugin_docman', 'details_statistics_byte_symbol');

        $s = array('', 'k', 'M', 'G', 'T', 'P');

        if ($bytes > 0) {
            $e = floor(log($bytes)/log(1024));
            $displayedSize = round($bytes/pow(1024, floor($e)), 2);
        } else {
            $e = 0;
            $displayedSize = 0;
        }

        return $displayedSize.' '.$s[$e].$byteSymbol;
    }
}
