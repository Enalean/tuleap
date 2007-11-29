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

require_once('common/plugin/Plugin.class.php');
require_once('UserLogDao.class.php');

class userlogPlugin extends Plugin {

	function userlogPlugin($id) {
		$this->Plugin($id);
        //$this->_addHook('hook_name');
        $this->_addHook('cssfile',               'cssFile', false);
        $this->_addHook('logger_after_log_hook', 'logUser', false);
	}

    function &getPluginInfo() {
        if (!is_a($this->pluginInfo, 'UserLogPluginInfo')) {
            require_once('UserLogPluginInfo.class.php');
            $this->pluginInfo =& new UserLogPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    function cssFile($params) {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if (strpos($_SERVER['REQUEST_URI'], $this->_getPluginPath()) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'.$this->_getThemePath().'/css/style.css" />'."\n";
        }
    }

    function &getDao() {
        $da =& CodexDataAccess::instance();
        $dao =& new UserLogDao($da);
        return $dao;
    }

    /**
     * $params['isScript']
     * $params['groupId']
     * $params['time']
     */
    function logUser($params) {
        if(!$params['isScript']) {
            $uid = 0;
            $uid = user_getid();

            $request = HTTPRequest::instance();

            $cookie_manager =& new CookieManager();

            $dao =& $this->getDao();
            $dao->addRequest($params['time'],
                             $params['groupId'],
                             $uid,
                             $cookie_manager->getCookie('session_hash'),
                             $request->getFromServer('HTTP_USER_AGENT'),
                             $request->getFromServer('REQUEST_METHOD'),
                             $request->getFromServer('REQUEST_URI'),
                             $request->getFromServer('REMOTE_ADDR'),
                             $request->getFromServer('HTTP_REFERER'));
        }
    }

    function displayNewOrIdem($key, $row, &$pval, $display = null) {
        $dis = '';
        if($pval[$key] != $row[$key]) {
            if($display === null) {
                $dis = $row[$key];
            } else {
                $dis = $display;
            }
        } else {
            $dis = '-';
        }
        if($dis == '') {
            $dis = '&nbsp;';
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

    function process() {
        $dao =& $this->getDao();

        $request = new HTTPRequest();

        $offset = intval($request->get('offset'));

        $count = 100;
        $dar = $dao->search($offset, $count);

        $foundRows = $dao->getFoundRows();


        $prevHref = '&lt;Previous';
        if($offset > 0) {
            $prevOffset = $offset - $count;
            if($prevOffset < 0) {
                $prevOffset = 0;
            }
            $prevHref = '<a href="?offset='.$prevOffset.'">'.$prevHref.'</a>';
        }
        $nextHref = 'Next&gt;';
        $nextOffset = $offset + $count;
        if($nextOffset > $foundRows) {
            $nextOffset = null;
        } else {
            $nextHref = '<a href="?offset='.$nextOffset.'">'.$nextHref.'</a>';
        }

        $pval = array();
        $this->initPval($pval);

        $GLOBALS['Response']->header(array('title' => 'userlog'));

        echo $prevHref." - ".$nextHref."<br>";

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
                $classStyle = ' class="hourbreak"';
                echo '<tr'.$classStyle.'>';
                $year = date('Y', $row['time']);
                $month = date('M', $row['time']);
                $day = date('d', $row['time']);
                $hour = date('H', $row['time']);
                $nexthour = date('H', mktime($hour+1, 0, 0, $month, $day, $year));
                echo '<td colspan="9">'.$day.' '.$month.' '.$year.' between '.$hour.' and '.$nexthour.' hour</td>';
                $this->initPval($pval);
            } else {
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
            }
            $pval['hour'] = date('H', $row['time']);
            echo '</tr>';
            $dar->next();
        }
        echo '</tbody>';
        echo '</table>';

        echo $prevHref." - ".$nextHref."<br>";

        $GLOBALS['Response']->footer(array());

    }

}

?>
