<?php
/**
 * Copyright (c) STMicroelectronics, 2007. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2007.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once('UserLogDao.class.php');

class UserLogManager {
    function UserLogManager() {

    }

    function &getDao() {
        $da =& CodexDataAccess::instance();
        $dao =& new UserLogDao($da);
        return $dao;
    }

    function logAccess($time, $gid, $uid, $sessionHash, $userAgent, $requestMethod, $requestUri, $remoteAddr, $httpReferer) {
        $dao =& $this->getDao();
        $dao->addRequest($time, $gid, $uid, $sessionHash, $userAgent, $requestMethod, $requestUri, $remoteAddr, $httpReferer);
    }

    function displayNewOrIdem($key, $row, &$pval, $display = null) {
        $dis = '';
        if($pval[$key] != $row[$key]) {
            if($display === null) {
                $dis = $row[$key];
            } else {
                $dis = $display;
            }
            // Display treatment
            if($dis == '') {
                $dis = '&nbsp;';
            } else {
                $hp = CodeX_HTMLPurifier::instance();
                $dis = $hp->purify($dis);
            }
        } else {
            $dis = '-';
        }

        $pval[$key] = $row[$key];
        return $dis;
    }

    function initPval(&$pval) {
        $pval = array('time' => -1,
                      'hour' => -1,
                      'group_id' => -1,
                      'user_id' => -1,
                      'session_hash' => -1,
                      'http_user_agent' => -1,
                      'http_request_method' => -1,
                      'http_request_uri' => -1,
                      'http_remote_addr' => -1,
                      'http_referer' => -1);
    }

    function displayLogs($offset, $selectedDay=null) {
        $dao =& $this->getDao();

        $year  = null;
        $month = null;
        $day   = null;
        if($selectedDay !== null) {
            if(preg_match('/^([0-9]+)-([0-9]{1,2})-([0-9]{1,2})$/', $selectedDay, $match)) {
                $year  = $match[1];
                $month = $match[2];
                $day   = $match[3];
            }
        }
        if($year === null) {
            //
            // Default dates
            $year  = date('Y');
            $month = date('n');
            $day   = date('j');
        }

        $start = mktime(0,0,0,$month,$day,$year);
        $end   = mktime(23,59,59,$month,$day,$year);
        $count = 100;

        $dar = $dao->search($start, $end, $offset, $count);
        $foundRows = $dao->getFoundRows();

        //
        // Prepare Navigation bar
        $hrefDay='day='.$year.'-'.$month.'-'.$day;
        $prevHref = '&lt;Previous';
        if($offset > 0) {
            $prevOffset = $offset - $count;
            if($prevOffset < 0) {
                $prevOffset = 0;
            }
            $prevHref = '<a href="?'.$hrefDay.'&amp;offset='.$prevOffset.'">'.$prevHref.'</a>';
        }
        $nextHref = 'Next&gt;';
        $nextOffset = $offset + $count;
        if($nextOffset > $foundRows) {
            $nextOffset = null;
        } else {
            $nextHref = '<a href="?'.$hrefDay.'&amp;offset='.$nextOffset.'">'.$nextHref.'</a>';
        }

        //
        // Init previous value
        $pval = array();
        $this->initPval($pval);

        //
        // Start display
        $GLOBALS['Response']->header(array('title' => 'userlog'));

        echo '<form name="userlog_form" method="get" action="?">';
        echo html_field_date('day',
                             $year.'-'.$month.'-'.$day,
                             false,
                             '10',
                             '10',
                             'userlog_form');
        echo ' ';
        echo '<input type="submit" value="Submit">';
        echo '</form>';


        echo $prevHref." - (".$foundRows." results found)  - ".$nextHref."<br>";

        echo '<table border="1">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>Time</th>';
        echo '<th>Project</th>';
        echo '<th>User</th>';
        //echo '<th>SessionHash</th>';
        //echo '<th>User Agent</th>';
        echo '<th>Method</th>';
        echo '<th>URI</th>';
        echo '<th>Remote addr</th>';
        echo '<th>Referer</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        $dar->rewind();
        while($dar->valid()) {
            $row = $dar->current();
            $classStyle = '';
            if($pval['hour'] != date('H', $row['time'])) {
                //
                //Change day
                $classStyle = ' class="hourbreak"';
                $year = date('Y', $row['time']);
                $month = date('M', $row['time']);
                $day = date('d', $row['time']);
                $hour = date('H', $row['time']);
                echo '<tr'.$classStyle.'>';
                $nexthour = date('H', mktime($hour+1, 0, 0, $month, $day, $year));
                echo '<td colspan="9">'.$day.' '.$month.' '.$year.' between '.$hour.' and '.$nexthour.' hour</td>';
                $this->initPval($pval);
                echo '</tr>';
            }
            $classStyle = '';
            echo '<tr'.$classStyle.'>';
            echo '<td>'.$this->displayNewOrIdem('time', $row, $pval, date('H:i:s', $row['time'])).'</td>';
            echo '<td>'.$this->displayNewOrIdem('group_id', $row, $pval).'</td>';
            $name = 'Anonymous';
            if($row['user_id'] != 0) {
                $name = user_getname($row['user_id']);
            }
            echo '<td>'.$this->displayNewOrIdem('user_id', $row, $pval, $name).'</td>';
            //echo '<td>'.$this->displayNewOrIdem('session_hash', $row, $pval).'</td>';
            //echo '<td>'.$this->displayNewOrIdem('http_user_agent', $row, $pval).'</td>';
            echo '<td>'.$this->displayNewOrIdem('http_request_method', $row, $pval).'</td>';
            echo '<td>'.$this->displayNewOrIdem('http_request_uri', $row, $pval).'</td>';
            echo '<td>'.$this->displayNewOrIdem('http_remote_addr', $row, $pval).'</td>';
            echo '<td>'.$this->displayNewOrIdem('http_referer', $row, $pval).'</td>';
            echo '</tr>';

            $pval['hour'] = date('H', $row['time']);
            $dar->next();
        }
        echo '</tbody>';
        echo '</table>';

        echo $prevHref." - (".$foundRows." results found)  - ".$nextHref."<br>";

        $GLOBALS['Response']->footer(array());
    }

}

?>
