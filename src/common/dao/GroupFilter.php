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

require_once 'StatementInterface.php';

/**
 * return SQL statements to search group by name or unix group name
 */
class GroupNameFilter implements iStatement
{
    /**
     * $_name
     *
     * @type string $_name
     */
    private $_name;

    /**
     * constructor
     *
     * @param string $name a string that matches with a part of group name or unix group name
     */
    function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getJoin()
    {
    }

    /**
     * the "WHERE" statements
     * 
     * @return string
     */
    function getWhere()
    {
        return '(group_name LIKE \'%'.$this->_name.'%\' OR unix_group_name LIKE \'%'.$this->_name.'%\')';
    }
    /**
     * not defined in the search
     *
     * @return void
     */
    function getGroupBy()
    {
    }
}

/**
 * return SQL statements to search group by state
 */
class GroupStateFilter implements iStatement
{
    /**
     * $_state
     *
     * @type string $_state
     */
    private $_state;

    /**
     * constructor
     *
     * @param int $state an int that matches with group state
     */
    function __construct($state)
    {
        $this->_state = $state;
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getJoin()
    {
    }

    /**
     * the "WHERE" statements
     * 
     * @return string
     */
    function getWhere()
    {
        return '(is_public ='.$this->_state.')';
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getGroupBy()
    {
    }
}

/**
 * return SQL statements to search group by type
 */
class GroupTypeFilter implements iStatement
{
    /**
     * $_type
     *
     * @type string $_type
     */
    private $_type;

     /**
     * constructor
     *
     * @param int $type an int that matches with group type
     */
    function __construct($type)
    {
        $this->_type = $type;
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getJoin()
    {
    }

    /**
     * the "WHERE" statements
     * 
     * @return string
     */
    function getWhere()
    {
        return '(type = '.$this->_type.')';
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getGroupBy()
    {
    }
}

/**
 * return SQL statements to search group by status
 */
class GroupStatusFilter implements iStatement
{
    /**
     * $_status
     *
     * @type string $_status
     */
    private $_status;

     /**
     * constructor
     *
     * @param string $status a string that matches with group status
     */
    function __construct($status)
    {
        $this->_status = $status;
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getJoin()
    {
    }

    /**
     * the "WHERE" statements
     * 
     * @return string
     */
    function getWhere()
    {
        return 'groups.status = \''.$this->_status.'\'';
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getGroupBy()
    {
    }
}

/**
 * return SQL statements to search group by name shortcut
 */
class GroupShortcutFilter implements iStatement
{
    /**
     * $_shortcut
     *
     * @type string $_shortcut
     */
    private $_shortcut;

     /**
     * constructor
     *
     * @param string $shortcut a string that matches with group first letter
     */
    function __construct($shortcut)
    {
        $this->_shortcut = $shortcut;
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getJoin()
    {
    }

    /**
     * the "WHERE" statements
     * 
     * @return string
     */
    function getWhere() 
    {
        return '(group_name LIKE \''.$this->_shortcut.'%\')';
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getGroupBy()
    {
    }
}

/**
 * return SQL statements to search group by group id
 */
class GroupIdFilter implements iStatement
{
    /**
     * $_groupid
     *
     * @type string $_groupid
     */
    private $_groupid;

     /**
     * constructor
     *
     * @param int $groupid an int that matches with group id
     */
    function __construct($groupid)
    {
        $this->_groupid = $groupid;
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getJoin()
    {
    }

    /**
     * the "WHERE" statements
     * 
     * @return string
     */
    function getWhere()
    {
        return '(groups.group_id = '.$this->_groupid.')';
    }

    /**
     * not defined in the search
     *
     * @return void
     */
    function getGroupBy() 
    {
    }
}

?>
