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

require_once('common/dao/include/DataAccessObject.class.php');

class UserLogDao extends DataAccessObject {

    function UserLogDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    function getFoundRows() {
        $sql = 'SELECT FOUND_ROWS() as nb';
        $dar = $this->retrieve($sql);
        if($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->current();
            return $row['nb'];
        } else {
            return -1;
        }
    }

    function search($offset, $count) {
        $sql = 'SELECT SQL_CALC_FOUND_ROWS *'.
            ' FROM plugin_userlog_request'.
            ' ORDER BY time DESC'.
            ' LIMIT '.$this->da->escapeInt($offset).', '.$this->da->escapeInt($count);
        return $this->retrieve($sql);
    }

    function addRequest($time, $gid, $uid, $sessionHash, $userAgent, $requestMethod, $requestUri, $remoteAddr, $httpReferer) {
        $sql = 'INSERT INTO plugin_userlog_request'.
            '(time,group_id,user_id,session_hash,http_user_agent,http_request_method,http_request_uri,http_remote_addr,http_referer)'.
            ' VALUES '.
            '('.
            $this->da->escapeInt($time).','.
            $this->da->escapeInt($gid).','.
            $this->da->escapeInt($uid).','.
            '"'.$this->da->escapeString($sessionHash).'",'.
            '"'.$this->da->escapeString($userAgent).'",'.
            '"'.$this->da->escapeString($requestMethod).'",'.
            '"'.$this->da->escapeString($requestUri).'",'.
            '"'.$this->da->escapeString($remoteAddr).'",'.
            '"'.$this->da->escapeString($httpReferer).'"'.
            ')';
        return $this->update($sql);
    }

}

?>
