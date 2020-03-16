<?php
/**
 *
 * Copyright (c) STMicroelectronics, 2006
 *
 * Originally written by Manuel VACELET, STMicroelectronics, 2006
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('Docman_View_Browse.class.php');

class Docman_View_Table extends Docman_View_Browse
{

    /**
     * @access: protected
     */
    public function _content($params)
    {
        $itemFactory = new Docman_ItemFactory($params['group_id']);

        // Limit browsing
        $offset = 25;
        $_low_limit  = 0;
        if (isset($params['start'])) {
            $_low_limit  = (int) $params['start'];
        }
        $_high_limit = $_low_limit + $offset;

        $nbItemsFound = 0;

        $itemIterator = $itemFactory->getItemList(
            $params['item']->getId(),
            $nbItemsFound,
            array('user' => $params['user'],
                                                    'ignore_collapse' => true,
                                                    'ignore_obsolete' => true,
                                                    'filter' => $params['filter'],
                                                    'start' => $_low_limit,
            'offset' => $offset)
        );

        // Default URL
        $this->_getDefaultUrlParams($params);

        // Generate table header
        $ci = $params['filter']->getColumnIterator();
        $ci->rewind();
        $table = '<table border="0" cellspacing="1" cellpadding="2" width="100%" data-test="docman_report_table">';
        $table .= '<tr class="boxtable">';
        while ($ci->valid()) {
            $column = $ci->current();
            $table .= '<td class="boxtitle">' . $column->getTitle($this, $params) . '</td>';
            $ci->next();
        }

        $table .= '</tr>';
        // Generate table
        $altRowClass = 0;
        $itemIterator->rewind();
        while ($itemIterator->valid()) {
            $item = $itemIterator->current();
            $trclass = html_get_alt_row_color($altRowClass++);
            $table .=  "<tr class=\"" . $trclass . "\">\n";
            $ci->rewind();
            while ($ci->valid()) {
                $column = $ci->current();
                $table .= "<td>";
                $table .= $column->getTableBox($item, $this, $params);
                $this->javascript .= $column->getJavascript($item, $this);
                $table .= "</td>\n";
                $ci->next();
            }
            $table .=  "</tr>\n";
            $itemIterator->next();
        }
        $table .= "</table>\n";

        // Prepare Navigation Bar
        if ($_low_limit > 0) {
            $firstUrl    = $this->_buildSearchUrl($params, array('start' => '0'));
            $first       = '<a href="' . $firstUrl . '">&lt;&lt; ' . dgettext('tuleap-docman', 'Begin') . '</a>';
        } else {
            $first       = '&lt;&lt; ' . dgettext('tuleap-docman', 'Begin');
        }

        $previousOffset = $_low_limit - $offset;
        if ($_low_limit > 0) {
            if ($previousOffset < 0) {
                $previousOffset = 0;
            }
            $previousUrl = $this->_buildSearchUrl($params, array('start' => $previousOffset));
            $previous    = '<a href="' . $previousUrl . '">&lt; ' . sprintf(dgettext('tuleap-docman', 'Previous %1$s'), $offset) . '&gt;</a>';
        } else {
            $previous    = '&lt; ' . sprintf(dgettext('tuleap-docman', 'Previous %1$s'), $offset);
        }

        if ($_high_limit < $nbItemsFound) {
            $nextUrl     = $this->_buildSearchUrl($params, array('start' => $_high_limit));
            $next        = '<a href="' . $nextUrl . '">' . sprintf(dgettext('tuleap-docman', 'Next %1$s'), $offset) . ' &gt;</a>';
        } else {
            $next        = sprintf(dgettext('tuleap-docman', 'Next %1$s'), $offset) . ' &gt;';
        }

        if ($_high_limit < $nbItemsFound) {
            $lastOffset  = $nbItemsFound - $offset;
            $lastUrl     = $this->_buildSearchUrl($params, array('start' => $lastOffset));
            $last        = '<a href="' . $lastUrl . '">' . dgettext('tuleap-docman', 'End') . ' &gt;&gt;</a>';
        } else {
            $last        = dgettext('tuleap-docman', 'End') . ' &gt;&gt;';
        }

        $navbar = '<table border="0" width="100%"><tr><td align="left">' . $first . ' ' . $previous . '</td><td align="center">' . $nbItemsFound . ' ' . dgettext('tuleap-docman', 'Documents found') . '</td><td align="right">' . $next . ' ' . $last . '</td></tr></table>';

        if (isset($params['filter']) && $params['filter'] !== null) {
            $htmlReport = new Docman_ReportHtml($params['filter'], $this, $params['default_url']);
            print $htmlReport->getReportCustomization();
        }
        print $navbar;
        print $table;
    }
}
