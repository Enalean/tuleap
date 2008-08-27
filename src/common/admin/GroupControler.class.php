
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
require_once 'pre.php';
require_once 'common/admin/view/AdminSearchDisplay.class.php';
require_once 'common/admin/view/group/GroupSearchDisplay.class.php';
require_once 'common/dao/CodexDataAccess.class.php';
require_once 'common/dao/GroupDao.class.php';
require_once 'common/mvc/Controler.class.php';

/**
 * GroupControler()
 */
class GroupControler extends Controler
{
    /**
     * $_mainGroupIterator
     *
     * @type Iterator $_mainGroupIterator
     */
    private $_mainGroupIterator;

    /**
     * $_adminEmailIterator
     *
     * @type Iterator $_adminEmailIterator
     */
    private $_adminEmailIterator;

    /**
     * $_groupArray
     *
     * @type mixed $_groupArray
     */
    //    private $_groupArray;

    /** 
     * $_limit
     *
     * @type int $_limit
     */
    private $_limit;

    /**
     * $_offset
     *
     * @type int $_offset
     */
    private $_offset;

    /**
     * $_nbuser
     *
     * @type int $_nbgroup
     */
    private $_nbgroup;

    /**
     * $_shortcut
     *
     * @type string $_shortcut
     */
    private $_shortcut;

    /**
     * $_name
     *
     * @type string $_name
     */
    private $_name;

    /**
     * $_status
     *
     * @type string $_status
     */
    private $_status;

    /**
     * $_state
     *
     * @type string $_state
     */
    private $_state;

    /**
     * $_type
     *
     * @type string $_type
     */
    private $_type;

    /**
     * constructor
     *
     */    
    function __construct() 
    {

    }

    /**
     * viewManagement()
     *
     * @return void
     */
    function viewsManagement() 
    {        
        $groupSearchDisplay = new GroupSearchDisplay($this->_mainGroupIterator, 
                                                     $this->_adminEmailIterator, 
                                                     $this->_offset, 
                                                     $this->_limit, 
                                                     $this->_nbgroup, 
                                                     $this->_shortcut, 
                                                     $this->_name, 
                                                     $this->_status, 
                                                     $this->_state, 
                                                     $this->_type);
       
        $groupSearchDisplay->display();
    }

    /**
     * setNbUser()
     *
     * @return void
     */
    function setNbGroup() 
    {
        $dao            = new GroupDao(CodexDataAccess::instance());
        $this->_nbgroup = $dao->getFoundRows();
    }

    /**
     * setOffset()
     *
     * @return void
     */
    function setOffset() 
    {
        $request =& HTTPRequest::instance();

        $validoffset = new Valid_UInt('offset');
        $validoffset->required();

        if ($request->valid($validoffset)) {
            $offset        = $request->get('offset');
            $this->_offset = $offset;
        } else {
            $this->_offset = 0;
        }
    }

    /**
     * setLimit()
     *
     * @return void
     */
    function setLimit() 
    {
        $request =& HTTPRequest::instance();

        //valid parameters

        //valid limit
        $limit      = '';
        $validLimit = new Valid_UInt('limit');

        if ($request->valid($validLimit)) {
            $limit = $request->get('limit');
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_limit'));
        }

        //valid nbrows
        $nbrows      = '';
        $validNbRows = new Valid_UInt('nbrows');

        if ($request->valid($validNbRows)) {
            $nbrows = $request->get('nbrows');
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_limit'));
        }

        if ($limit != '') {
            $this->_limit = $limit;
        } elseif ($nbrows != '') {
            $this->_limit = $nbrows;
        } else {
            $this->_limit = 50;
        }
    }

    /**
     * setMainGroupIterator()
     *
     * @return void
     */
    function setMainGroupIterator() 
    {
        $dao = new GroupDao(CodexDataAccess::instance());

        $filter = array();

        $request =& HTTPRequest::instance();

        //define white lists for parameters
        $shortcutWhiteList = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 
                                   'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 
                                   'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', 
                                   '4', '5', '6', '7', '8', '9');

        $statusWhiteList = array('all', 'I', 'A', 'P', 'H', 'D');

        $stateWhiteList = array('any', '0', '1');

        $typeWhiteList = array('any', '1', '2', '3');

        //valid parameters

        //valid shortcut
        $validShortcut = new Valid('group_shortcut_search');
        $validShortcut->addRule(new Rule_WhiteList($shortcutWhiteList));

        if ($request->valid($validShortcut)) {
            $this->_shortcut = $request->get('group_shortcut_search');
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_shortcut'));
        }

        //valid group name
        $validGroupName = new Valid_String('group_name_search');

        if ($request->valid($validGroupName)) {
            $this->_name = $request->get('group_name_search');
            $this->_name = explode(',', $this->_name);
            $this->_name = $this->_name[0];

            if ( preg_match('#^.*\((.*)\)$#', $this->_name, $matches)) {
                $this->_name = $matches[1];
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_group_name'));
        }

        //valid status
        $validStatus = new Valid('group_status_search');
        $validStatus->addRule(new Rule_WhiteList($statusWhiteList));

        if ($request->valid($validStatus)) {
            $this->_status = $request->get('group_status_search');                
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_status'));
        }

        //valid state
        $validState = new Valid('group_state_search');
        $validState->addRule(new Rule_WhiteList($stateWhiteList));

        if ($request->valid($validState)) {
            $this->_state = $request->get('group_state_search');
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_state'));
        }

        //valid type
        $validType = new Valid('group_type_search');
        $validType->addRule(new Rule_WhiteList($typeWhiteList));

        if ($request->valid($validType)) {
            $this->_type = $request->get('group_type_search');
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_type'));
        }

        if ($this->_shortcut != '') {
                $filter[] = new GroupShortcutFilter($this->_shortcut);
        }
        if ($this->_name != '') {
                $filter[] = new GroupNameFilter($this->_name);
        }
        if ($this->_status != '' && $this->_status != 'all') {
            $filter[] = new GroupStatusFilter($this->_status);
        }
        if ($this->_state != '' && $this->_state != 'any') {
            $filter[] = new GroupStateFilter($this->_state);
        }
        if ($this->_type != '' && $this->_type != 'any') {
            $filter[] = new GroupTypeFilter($this->_type);
        }
        $this->_mainGroupIterator = $dao->searchGroupByFilter($filter, 
                                                              $this->_offset, 
                                                              $this->_limit);
    }

    /**
     * setAdminEmailIterator
     *
     * @return void
     */
    function setAdminEmailIterator() 
    {
        $dao = new GroupDao(CodexDataAccess::instance());

        $filter = array();

        $request =& HTTPRequest::instance();

        //define white lists for parameters
        $shortcutWhiteList = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 
                                   'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 
                                   'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', 
                                   '4', '5', '6', '7', '8', '9');

        $statusWhiteList = array('all', 'I', 'A', 'P', 'H', 'D');

        $stateWhiteList = array('any', '0', '1');

        $typeWhiteList = array('any', '1', '2', '3');

        //valid parameters

        //valid shortcut
        $validShortcut = new Valid('group_shortcut_search');
        $validShortcut->addRule(new Rule_WhiteList($shortcutWhiteList));

        if ($request->valid($validShortcut)) {
            $this->_shortcut = $request->get('group_shortcut_search');
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_shortcut'));
        }

        //valid group name
        $validGroupName = new Valid_String('group_name_search');

        if ($request->valid($validGroupName)) {
            $this->_name = $request->get('group_name_search');
            $this->_name = explode(',', $this->_name);
            $this->_name = $this->_name[0];

            if ( preg_match('#^.*\((.*)\)$#', $this->_name, $matches)) {
                $this->_name = $matches[1];
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_group_name'));
        }

        //valid status
        $validStatus = new Valid('group_status_search');
        $validStatus->addRule(new Rule_WhiteList($statusWhiteList));

        if ($request->valid($validStatus)) {
            $this->_status = $request->get('group_status_search');                
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_status'));
        }

        //valid state
        $validState = new Valid('group_state_search');
        $validState->addRule(new Rule_WhiteList($stateWhiteList));

        if ($request->valid($validState)) {
            $this->_state = $request->get('group_state_search');
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_state'));
        }

        //valid type
        $validType = new Valid('group_type_search');
        $validType->addRule(new Rule_WhiteList($typeWhiteList));

        if ($request->valid($validType)) {
            $this->_type = $request->get('group_type_search');
        } else {
            $GLOBALS['Response']->addFeedback('error', 
                                              $GLOBALS['Language']->getText('admin_group_controler', 'wrong_type'));
        }

        if ($this->_shortcut != '') {
                $filter[] = new GroupShortcutFilter($this->_shortcut);
        }
        if ($this->_name != '') {
            $filter[] = new GroupNameFilter($this->_name);
        }
        if ($this->_status != '' && $this->_status != 'all') {
            $filter[] = new GroupStatusFilter($this->_status);
        }
        if ($this->_state != '' && $this->_state != 'any') {
            $filter[] = new GroupStateFilter($this->_state);
        }
        if ($this->_type != '' && $this->_type != 'any') {
            $filter[] = new GroupTypeFilter($this->_type);
        }

        $this->_adminEmailIterator = $dao->searchAdminEmailByFilter($filter);        
    }

    /**
     * request()
     *
     * @return void
     */
    function request() 
    {
        $this->setOffset();

        $this->setLimit();

        $this->setAdminEmailIterator();

        $this->setMainGroupIterator();

        $this->setNbGroup();
    }
}

?>
