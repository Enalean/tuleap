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
    public $inheritableMetadataArray;
    public $_controller;

    public function __construct($item, $url, $controller)
    {
        $this->_controller = $controller;

        $id = 'statistics';
        $title = dgettext('tuleap-docman', 'Statistics');
        parent::__construct($item, $url, $id, $title);
    }

    public function _getPropertyRow($label, $value)
    {
        $html = '';
        $html .= '<tr style="vertical-align:top;">';
        $html .= '<td class="label">' . $label . '</td>';
        $html .= '<td class="value">' . $value . '</td>';
        $html .= '</tr>';
        return $html;
    }

    public function _getStatisticsFields($params)
    {
        $html = '';

        if (is_a($this->item, 'Docman_Folder')) {
            $if = Docman_ItemFactory::instance($this->_controller->getGroupId());
            $stats = $if->getFolderStats($this->item, $this->_controller->getUser());

            $size =  $this->convertBytesToHumanReadable($stats['size']);

            // Summary
            $html .= '<h3>' . dgettext('tuleap-docman', 'Summary') . '</h3>';
            $html .= '<table class="docman_item_details_properties">';
            $html .= $this->_getPropertyRow(dgettext('tuleap-docman', 'Size:'), $size);
            $html .= $this->_getPropertyRow(dgettext('tuleap-docman', 'Number of items in this folder:'), $stats['count']);
            $html .= '</table>';

            // Details
            if ($stats['count'] > 0) {
                $html .= '<h3>' . dgettext('tuleap-docman', 'Details') . '</h3>';
                $html .= '<p>' . dgettext('tuleap-docman', 'This table shows the number of elements of each type.') . '</p>';
                $html .= '<table class="docman_item_details_properties">';
                arsort($stats['types']);
                foreach ($stats['types'] as $type => $stat) {
                    $label = '';
                    switch (strtolower($type)) {
                        case 'file':
                            $label = dgettext('tuleap-docman', 'Files:');
                            break;
                        case 'wiki':
                            $label = dgettext('tuleap-docman', 'Wiki pages:');
                            break;
                        case 'embeddedfile':
                            $label = dgettext('tuleap-docman', 'Embedded files:');
                            break;
                        case 'empty':
                            $label = dgettext('tuleap-docman', 'Empty documents:');
                            break;
                        case 'link':
                            $label = dgettext('tuleap-docman', 'Links:');
                            break;
                        case 'folder':
                            $label = dgettext('tuleap-docman', 'Folders:');
                            break;
                    }
                    $html  .= $this->_getPropertyRow($label, $stat);
                }
                $html .= '</table>';
            }
        }

        return $html;
    }

    public function getContent($params = array())
    {
        $html = '<div class="docman_help">' . dgettext('tuleap-docman', '<ul><li>The whole folder sub-tree is taken in account for these statistics.</li><li>To compute the size, only the last versions of "file" and "embedded file" documents are taken in account.</li></ul>') . '</div>';
        $html .= $this->_getStatisticsFields($params);
        return $html;
    }

    private function convertBytesToHumanReadable($bytes)
    {
        $s = array('', 'k', 'M', 'G', 'T', 'P');

        if ($bytes > 0) {
            $e = floor(log($bytes) / log(1024));
            $displayedSize = round($bytes / pow(1024, floor($e)), 2);
        } else {
            $e = 0;
            $displayedSize = 0;
        }

        return $displayedSize . ' ' . $s[$e] . 'B';
    }
}
