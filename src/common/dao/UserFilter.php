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
 * init SQL request to search user by name
 */
class UserNameFilter implements iStatement
{
    /**
     * $_name
     *
     * @type string $_name
     */
    private $_name;

    /**
     * Constructor
     *
     * @param string $name a part of a user name
     */
    function __construct($name)
    {
        $cleanname   = db_escape_string($name);
        $this->_name = $cleanname;
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
        return '(user_name LIKE \'%'.$this->_name.'%\' OR realname LIKE \'%'.$this->_name.'%\')';
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
 * init SQL request to search user by group name
 */
class UserGroupFilter implements iStatement
{
    /**
     * $_group
     *
     * @type string $_group
     */
    private $_group;

     /**
     * Constructor
     *
     * @param string $group a part of a group name
     */
    function __construct($group)
    {
        $cleangroup   = db_escape_string($group);
        $this->_group = $cleangroup;
    }

    /**
     * the "JOIN" statements
     * 
     * @return string
     */
    function getJoin()
    {
        return  'user_group ON (user.user_id = user_group.user_id) JOIN groups ON (user_group.group_id = groups.group_id)';
    }

    /**
     * the "WHERE" statements
     * 
     * @return string
     */
    function getWhere()
    {
        return '(groups.group_name LIKE \'%'.$this->_group.'%\' OR groups.unix_group_name LIKE \'%'.$this->_group.'%\')';
    }

     /**
     * not defined in the search
     *
     * @return void
     */
    function getGroupBy()
    {
        return 'user.user_id';
    }
}

/**
 * init SQL request to search user by status
 */
class UserStatusFilter implements iStatement
{
    /**
     * $_status
     *
     * @type string $_status
     */
    private $_status;

     /**
     * Constructor
     *
     * @param string $status the user status
     */
    function __construct($status)
    {
        $cleanstatus   = db_escape_string($status);
        $this->_status = $cleanstatus;
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
        return 'user.status = \''.$this->_status.'\'';
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
 * init SQL request to search user by shortcut
 *
 */
class UserShortcutFilter implements iStatement
{
    /**
     * $_shortcut
     *
     * @type string $_shortcut
     */
    private $_shortcut;

     /**
     * Constructor
     *
     * @param string $shortcut the user shortcut
     */
    function __construct($shortcut)
    {
        $cleanshortcut   = db_escape_string($shortcut);
        $this->_shortcut = $cleanshortcut;
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
        return '(user_name LIKE \''.$this->_shortcut.'%\')';
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
 * init SQL request to search a user by his user_name
 * I call this method FullUserNameFilter because the whole name
 * has to match with the parameter
 */
class FullUserNameFilter implements iStatement
{

    /**
     * $_name
     *
     * @type string $_name
     */
    private $_name;

     /**
     * Constructor
     *
     * @param string $name the user name
     */
    function __construct($name)
    {
        $cleanname   = db_escape_string($name);
        $this->_name = $cleanname;
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
        return '(user_name = \''.$this->_name.'\')';
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
 * init SQL request to search the groups a user belong to by his (user) name
 *
 */
class UserGroupByNameFilter implements iStatement
{
    /**
     * $_name
     *
     * @type string $_name
     */
    private $_name;

     /**
     * Constructor
     *
     * @param string $name the user name
     */
    function __construct($name)
    {
        $cleanname   = db_escape_string($name);
        $this->_name = $cleanname;
    }

     /**
     * not defined in the search
     *
     * @return void
     */
    function getJoin()
    {
        return 'user_group ON (user.user_id= user_group.user_id) JOIN groups ON (user_group.group_id = groups.group_id)';
    }

    /**
     * the "WHERE" statements
     * 
     * @return string
     */
    function getWhere()
    {
        return '(user_name = \''.$this->_name.'\')';
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
