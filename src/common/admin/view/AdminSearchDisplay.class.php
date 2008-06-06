<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Arnaud Salvucci, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */

/**
 * AdminSearchDisplay
 *
 */
require_once('common/admin/view/AdminDisplay.class.php');
class AdminSearchDisplay extends AdminDisplay {
    
    function __construct() {
        
    }

    /**
     * displayHeader()
     *
     * @param $header
     */
    function displayHeader($header) {
        parent::displayHeader($header);        
    }
    
    /**
     * displaySearchFilter
     *
     * @param array $abc_entitled
     * @param string $letterlink
     */
    function displaySearchFilter($abc_entitled, $link,$offset, $limit) {       

        $abc_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');
        
        print '<p>';
        print $abc_entitled;
        
        for ($i=0; $i < count($abc_array); $i++) {
            print '<a href="index.php?offset='.$offset.'&limit='.$limit.'&'.$link.'='. $abc_array[$i] .'">&nbsp;'. $abc_array[$i] .'&nbsp;</a>';
        }
        print '</p>';        
    }

    /**
     * displayBrowse
     *
     * @param int $start
     * @param int $end
     * @param int $offset
     * @param int $limit
     * @param int $nbuser
     * @param int $offsetmax
     * @param string $username
     * @param string $group
     * @param string $status
     */
    function displayBrowse($start, $end, $offset, $limit, $nbuser, $offsetmax, $link) {

      $prev = $offset - $limit;
     
      print '<table width=100%>';
        
      print '<tr>';

      if ($offset <= 0) {

 print '<td class="browse_left">&lt;&lt; '.$GLOBALS['Language']->getText('global', 'begin').'&lt; '.$GLOBALS['Language']->getText('global', 'prev').' '. $limit .'</span></td>';
      }
      else {
   
          print '<td class="browse_left"><a href="index.php?offset=0&limit='.$limit.$link.'">&lt;&lt; '.$GLOBALS['Language']->getText('global', 'begin').'</a>  <a href="index.php?offset='.$prev.'&limit='.$limit.$link.'">&lt; '.$GLOBALS['Language']->getText('global', 'prev').' '. $limit .'</a></span></td>';
      }

      print '<td class="browse_center">Items '.$start.' - '.$end.'</td>';

      if ($offset >= ($nbuser-$limit)|| $offsetmax <=0) {
          
 print '<td class="browse_right">'.$GLOBALS['Language']->getText('global', 'next').' '.$limit .'&gt;'.$GLOBALS['Language']->getText('global', 'end').' &gt;&gt;</td>';
      }
      else {
          
          print '<td class="browse_right"><a href="index.php?offset='.$end.'&limit='.$limit.$link.'">'.$GLOBALS['Language']->getText('global', 'next').' '.$limit .'&gt;</a>  <a href="index.php?offset='.$offsetmax.'&limit='.$limit.$link.'">'.$GLOBALS['Language']->getText('global', 'end').' &gt;&gt;</a></td>';

 }

      print '</tr>';
        
      print '</table>';
    }

    /**
     * display
     */
    function display() {

        //Header
        $this->displayHeader();
        
        //Search
        $this->displaySearchFilter();
        
        //Browsing
        $this->displayBrowse();
        
        //Search table
        $this->displaySearch();
        
        //Browsing
        $this->displayBrowse();
        
        //footer
        $this->displayFooter();
    }
}
?>
