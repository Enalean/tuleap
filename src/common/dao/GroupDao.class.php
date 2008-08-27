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

require_once 'include/DataAccessObject.class.php';
require_once 'GroupFilter.php';

/**
 *  Data Access Object for User 
 */
class GroupDao extends DataAccessObject
{
    /**
     * Constructs the GroupDao
     *
     * @param instance $da instance of the DataAccess class
     */
    function GroupDao( & $da ) 
    {
        DataAccessObject::DataAccessObject($da);
    }

    /**
     * Gets all tables of the db
     *
     * @param int $offset the offset of the sql request
     * @param int $limit  the limit of the sql requeset
     *
     * @return DataAccessResult
     *
     */
    function searchAll($offset=null, $limit=null) 
    {
        $this->sql = "SELECT SQL_CALC_FOUND_ROWS * FROM user";

        if ($offset !== null && $limit !== null) {
            $this->sql .= ' LIMIT '.$this->da->escapeInt($offset).','.$this->da->escapeInt($limit);
        }        
        return $this->retrieve($this->sql);
    }

    /**
     * search group by filter
     *
     * @param mixed $ca     an iteator that contains SQL statement
     * @param int   $offset the offset of the sql request
     * @param int   $limit  the limit of the sql requeset
     * 
     * @return DataAccessResult
     */
    function searchGroupByFilter($ca, $offset=null, $limit=null) 
    {
        $cleanoffset = db_escape_int($offset);
        $cleanlimit  = db_escape_int($limit);

        $sql = 'SELECT SQL_CALC_FOUND_ROWS groups.group_id, group_name, unix_group_name, groups.status, type, is_public, license, count(user.user_id) as c, name '. 
               'FROM group_type JOIN groups ON group_type.type_id = groups.type '.
               'LEFT JOIN user_group ON groups.group_id = user_group.group_id '.
               'LEFT JOIN user ON user_group.user_id = user.user_id';

        if (!empty($ca)) {

            $iwhere  = 0;
            $join    = null;
            $where   = null;
            $groupby = null;

            foreach ($ca as $c) {

                if ($c->getJoin()) {
                    $join .= $c->getJoin();
                }

                if ($iwhere >= 1) {
                    $where .= ' AND '.$c->getWhere();
                    $iwhere++;
                } else {
                    $where .= $c->getWhere();
                    $iwhere++;
                }

                if ($c->getGroupBy() !== null) {
                    $groupby .= $c->getGroupBy();
                }
            }  

            if ($join !== null) {
                $sql .= ' JOIN '.$join;
            }

            $sql .= ' WHERE '.$where;

            if ($groupby !== null) {
                $sql .= ' GROUP BY '.$groupby;
            }
        }

        $sql .= ' GROUP BY groups.group_id';
        $sql .= ' ORDER BY groups.group_id,groups.group_name';

        if ($cleanoffset != null && $cleanlimit != null) {
            $sql .= ' LIMIT '.$cleanoffset.', '.$cleanlimit;
        }
        return $this->retrieve($sql);
    }

    /**
     * search the email of groups admins
     *
     * @param mixed $ca an iteator that contains SQL statement
     *
     * @return DataAccessResult
     */
    function searchAdminEmailByFilter($ca) 
    {
        $sql = 'SELECT email,user_group.user_id, user_group.group_id'.
               ' FROM groups'.
               ' JOIN user_group ON (groups.group_id = user_group.group_id)'.
               ' JOIN user ON user_group.user_id = user.user_id'.
               ' WHERE admin_flags = \'A\'';

        if (!empty($ca)) {

            $where   = null;
            $groupby = null;

            foreach ($ca as $c) {

                if ($c->getWhere()) {
                    $where .= ' AND '.$c->getWhere();
                }

                if ($c->getGroupBy() !== null) {
                    $groupby .= $c->getGroupBy();
                }
            }  

            if ($groupby !== null) {
                $sql .= ' GROUP BY '.$groupby;
            }

            $sql .= $where;
            $sql .= ' ORDER BY groups.group_id,groups.group_name';
        }
        return $this->retrieve($sql);
    }

    /**
     * return the number of row of a sql resource or false if an error occured
     *
     * @return int
     */
    function getFoundRows() 
    {
        $sql = 'SELECT FOUND_ROWS() as nb';
        $dar = $this->retrieve($sql);
        if ($dar && !$dar->isError() && $dar->rowCount() == 1) {
            $row = $dar->getRow();
            return $row['nb'];
        } else {
            return false;
        }
    }
}

?>
