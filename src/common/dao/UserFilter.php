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
require_once('StatementInterface.php');

/**
 * init SQL request to search user by name
 */
class UserName implements iStatement {

    /**
     * $name
     *
     * @type string $name
     */
    private $name;

    function __construct($name) {
        $this->name = $name;
    }

    function getJoin() {}

    function getWhere() {
        return '(user_name LIKE \'%'.$this->name.'%\' OR realname LIKE \'%'.$this->name.'%\')';
    }

    function getGroupBy() {}
}

/**
 * init SQL request to search user by group name
 */
class UserGroup implements iStatement {

    /**
     * $group
     *
     * @type string $group
     */
    private $group;

    function __construct($group) {
        $this->group = $group;
    }

    function getJoin() {
        return  'user_group ON (user.user_id = user_group.user_id) JOIN groups ON (user_group.group_id = groups.group_id)';
    }

    function getWhere() {
        return '(groups.group_name LIKE \'%'.$this->group.'%\' OR groups.unix_group_name LIKE \'%'.$this->group.'%\')';
    }

    function getGroupBy() {
        return 'user.user_id';
    }
}

/**
 * init SQL request to search user by status
 */
class UserStatus implements iStatement {

    /**
     * $status
     *
     * @type string $status
     */
    private $status;

    function __construct($status) {
        $this->status = $status;
    }

    function getJoin() {}

    function getWhere() {
        return 'user.status = \''.$this->status.'\'';
    }

    function getGroupBy() {}
}

/**
 * init SQL request to search user by shortcut
 *
 */
class UserShortcut implements iStatement {

    /**
     * $shortcut
     *
     * @type string $shortcut
     */
    private $shortcut;

    function __construct($shortcut) {
        $this->shortcut = $shortcut;
    }

    function getJoin() {}

    function getWhere() {
        return '(user_name LIKE \''.$this->shortcut.'%\')';
    }

    function getGroupBy() {}
}
?>