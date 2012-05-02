<?php
/*
 * Copyright (c) STMicroelectronics, 2006
 * Originally written by Manuel VACELET, STMicroelectronics, 2006
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
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 *
 */
require_once('Docman_View_Browse.class.php');

class Docman_View_Table extends Docman_View_Browse {

    /**
     * @access: protected
     */
    function _content($params) {      
        $itemFactory = new Docman_ItemFactory($params['group_id']);

        // Limit browsing
        $offset = 25;
        $_low_limit  = 0;
        if(isset($params['start'])) {
            $_low_limit  = (int) $params['start'];
        }
        $_high_limit = $_low_limit + $offset;

        $nbItemsFound = 0;

        $itemIterator =& $itemFactory->getItemList($params['item']->getId(),
                                              $nbItemsFound,
                                              array('user' => $params['user'],
                                                    'ignore_collapse' => true,
                                                    'ignore_obsolete' => true,
                                                    'filter' => $params['filter'],
                                                    'start' => $_low_limit,
                                                    'offset' => $offset));
        
        // Default URL
        $this->_getDefaultUrlParams($params);
        $baseUrl = $this->buildActionUrl($params, array_merge($this->dfltParams, 
                                                              $this->dfltSearchParams));
        
        // Generate table header 
        $ci = $params['filter']->getColumnIterator();
        $ci->rewind();
        while($ci->valid()) {
            $column = $ci->current();
            $columnsTitles[] = $column->getTitle($this, $params);
            $ci->next();
        }
        $table = html_build_list_table_top($columnsTitles);
        
        // Generate table
        $altRowClass = 0;
        $itemIterator->rewind();
        while($itemIterator->valid()) {            
            $item =& $itemIterator->current();
            $trclass = html_get_alt_row_color($altRowClass++);
            $table .=  "<tr class=\"".$trclass."\">\n";
            $ci->rewind();
            while($ci->valid()) {
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
        if($_low_limit > 0) {
            $firstUrl    = $this->_buildSearchUrl($params, array('start' => '0'));
            $first       = '<a href="'.$firstUrl.'">&lt;&lt; '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_begin').'</a>';
        }
        else {
            $first       = '&lt;&lt; '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_begin');
        }
        
        $previousOffset = $_low_limit - $offset;
        if($_low_limit > 0) {
            if($previousOffset < 0) {
                $previousOffset = 0;
            }
            $previousUrl = $this->_buildSearchUrl($params, array('start' => $previousOffset));
            $previous    = '<a href="'.$previousUrl.'">&lt; '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_previous', $offset).'&gt;</a>';
        }
        else {
            $previous    = '&lt; '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_previous', $offset);
        }
         
        if($_high_limit < $nbItemsFound) {
            $nextUrl     = $this->_buildSearchUrl($params, array('start' => $_high_limit));
            $next        = '<a href="'.$nextUrl.'">'.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_next', $offset).' &gt;</a>';
        }
        else {
            $next        = $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_next', $offset).' &gt;';
        }

        if($_high_limit < $nbItemsFound) {
            $lastOffset  = $nbItemsFound - $offset;
            $lastUrl     = $this->_buildSearchUrl($params, array('start' => $lastOffset));
            $last        = '<a href="'.$lastUrl.'">'.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_end').' &gt;&gt;</a>';
        }
        else {
            $last        = $GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_end').' &gt;&gt;';
        }

        $navbar = '<table border="0" width="100%"><tr><td align="left">'.$first.' '.$previous.'</td><td align="center">'.$nbItemsFound.' '.$GLOBALS['Language']->getText('plugin_docman', 'view_documenttable_docfound').'</td><td align="right">'.$next.' '.$last.'</td></tr></table>';

        if(isset($params['filter']) && $params['filter'] !== null) {
            $htmlReport = new Docman_ReportHtml($params['filter'], $this, $params['default_url']);
            print $htmlReport->getReportCustomization($params);
        }
        print $navbar;
        print $table;        
    }
}
?>
