<?php
/* 
 * Copyright 2005, STMicroelectronics
 *
 * Originally written by Manuel Vacelet
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
 */

define('HTMLCLEANER_FULL', 0);
define('HTMLCLEANER_LOW',  1);

/**
 * @package WikiService
 */
class HTMLCleaner {

  
  function HTMLCleaner() {

  }

  function &_cleanLow($html) {
    $clean = preg_replace('/<script/i', '', $html);
    $clean = str_replace('<!--', '', $clean);
    $clean = str_replace('<![CDATA[', '', $clean);
    $clean = str_replace('<?', '', $clean);
    return $clean;
  }

  function &_cleanFull($html) {
    return htmlentities($html);
  }

  function &clean($html, $level=HTMLCLEANER_LOW) {
    switch($level) {
    case HTMLCLEANER_LOW:
      return HTMLCleaner::_cleanLow($html);
      break;
      
    case HTMLCLEANER_FULL:
    default:
      return HTMLCleaner::_cleanFull($html);
    }
  }
}

?>