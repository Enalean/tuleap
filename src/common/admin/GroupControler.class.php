
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
require_once('pre.php');
require_once('common/admin/view/AdminSearchDisplay.class.php');
require_once('common/admin/view/group/GroupSearchDisplay.class.php');
require_once('common/dao/CodexDataAccess.class.php');
require_once('common/dao/GroupDao.class.php');
require_once('common/mvc/Controler.class.php');


class GroupControler extends Controler {

    /**
     * $mainGroupIterator
     *
     * @type Iterator $mainGroupIterator
     */
    private $mainGroupIterator;

    /**
     * $adminEmailIterator
     *
     * @type Iterator $adminEmailIterator
     */
    private $adminEmailIterator;

    /**
     * $groupArray
     *
     * @type Array $groupArray
     */
    private $groupArray;

    /** 
     * $limit
     *
     * @type int $limit
     */
    private $limit;

    /**
     * $offset
     *
     * @type int $offset
     */
    private $offset;

    /**
     * $nbuser
     *
     * @type int $nbgroup
     */
    private $nbgroup;

    /**
     * $shortcut
     *
     * @type string $nbgroup
     */
    private $shortcut;

    /**
     * $name
     *
     * @type string $name
     */
    private $name;

    /**
     * $status
     *
     * @type string $status
     */
    private $status;

    /**
     * $state
     *
     * @type string $state
     */
    private $state;

    /**
     * $type
     *
     * @type string $type
     */
    private $type;

    /**
     * constructor
     *
     */    
    function __construct() {

    }

    /**
     * viewManagement()
     */
    function viewsManagement() {        
        $groupSearchDisplay = new GroupSearchDisplay($this->mainGroupIterator, $this->adminEmailIterator, $this->offset, $this->limit, $this->nbgroup, $this->shortcut, $this->name, $this->status, $this->state, $this->type);
        $groupSearchDisplay->display();       
    }

    /**
     * setNbUser()
     */
    function setNbGroup() {
        $dao = new GroupDao(CodexDataAccess::instance());
        $this->nbgroup = $dao->getFoundRows();
    }

    /**
     * setOffset()
     *
     * @param int $offset
     */
    function setOffset() {

        $request =& HTTPRequest::instance();

        $validoffset = new Valid_UInt('offset');
        $validoffset->required();

        if ($request->valid($validoffset)) {
            $offset = $request->get('offset');
            $this->offset = $offset;
        }
        else {
            $this->offset = 0;
        }
    }

    /**
     * setLimit()
     */
    function setLimit() {

        $request =& HTTPRequest::instance();

        //valid parameters

        //valid limit
        $limit = '';
        $validLimit = new Valid_UInt('limit');

        if($request->valid($validLimit)) {
            $limit = $request->get('limit');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_limit'));
        }

        //valid nbrows
        $nbrows = '';
        $validNbRows = new Valid_UInt('nbrows');

        if($request->valid($validNbRows)) {
            $nbrows = $request->get('nbrows');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_limit'));
        }

        if ($limit != '') {
            $this->limit = $limit;
        }
        elseif ($nbrows != '') {
            $this->limit = $nbrows;
        }
        else {
            $this->limit = 50;
        }
    }

    /**
     * setMainGroupIterator()
     */
    function setMainGroupIterator() {

        $dao = new GroupDao(CodexDataAccess::instance());

        $filter = array();

        $request =& HTTPRequest::instance();

        //define white lists for parameters
        $shortcutWhiteList = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        $statusWhiteList = array('all', 'I', 'A', 'P', 'H', 'D');

        $stateWhiteList = array('any', '0', '1');

        $typeWhiteList = array('any', '1', '2', '3');

        //valid parameters

        //valid shortcut
        $validShortcut = new Valid('group_shortcut_search');
        $validShortcut->addRule(new Rule_WhiteList($shortcutWhiteList));

        if($request->valid($validShortcut)) {
            $this->shortcut = $request->get('group_shortcut_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_shortcut'));
        }

        //valid group name
        $validGroupName = new Valid_String('group_name_search');

        if ($request->valid($validGroupName)) {
            $this->name = $request->get('group_name_search');
            $this->name = explode(',', $this->name);
            $this->name = $this->name[0];

            if ( preg_match('#^.*\((.*)\)$#',$this->name, $matches)) {
                $this->name = $matches[1];
            }
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_group_name'));
        }

        //valid status
        $validStatus = new Valid('group_status_search');
        $validStatus->addRule(new Rule_WhiteList($statusWhiteList));

        if ($request->valid($validStatus)) {
            $this->status = $request->get('group_status_search');                
        }
        else{
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_status'));
        }

        //valid state
        $validState = new Valid('group_state_search');
        $validState->addRule(new Rule_WhiteList($stateWhiteList));

        if ($request->valid($validState)) {
            $this->state = $request->get('group_state_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_state'));
        }

        //valid type
        $validType = new Valid('group_type_search');
        $validType->addRule(new Rule_WhiteList($typeWhiteList));

        if ($request->valid($validType)) {
            $this->type = $request->get('group_type_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_type'));
        }

        if ($this->shortcut != '') {
                $filter[] = new GroupShortcutFilter($this->shortcut);        
        }
        if ($this->name != '') {
                $filter[] = new GroupNameFilter($this->name);
        }
        if ($this->status != '' && $this->status != 'all') {
            $filter[] = new GroupStatusFilter($this->status);
        }
        if ($this->state != '' && $this->state != 'any') {
            $filter[] = new GroupStateFilter($this->state);
        }
        if ($this->type != '' && $this->type != 'any') {
            $filter[] = new GroupTypeFilter($this->type);
        }
        $this->mainGroupIterator = $dao->searchGroupByFilter($filter, $this->offset, $this->limit);
    }

    /**
     * setAdminEmailIterator
     */
    function setAdminEmailIterator() {

        $dao = new GroupDao(CodexDataAccess::instance());

        $filter = array();

        $request =& HTTPRequest::instance();

        //define white lists for parameters
        $shortcutWhiteList = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9');

        $statusWhiteList = array('all', 'I', 'A', 'P', 'H', 'D');

        $stateWhiteList = array('any', '0', '1');

        $typeWhiteList = array('any', '1', '2', '3');

        //valid parameters

        //valid shortcut
        $validShortcut = new Valid('group_shortcut_search');
        $validShortcut->addRule(new Rule_WhiteList($shortcutWhiteList));

        if($request->valid($validShortcut)) {
            $this->shortcut = $request->get('group_shortcut_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_shortcut'));
        }

        //valid group name
        $validGroupName = new Valid_String('group_name_search');

        if ($request->valid($validGroupName)) {
            $this->name = $request->get('group_name_search');
            $this->name = explode(',', $this->name);
            $this->name = $this->name[0];

            if ( preg_match('#^.*\((.*)\)$#',$this->name, $matches)) {
                $this->name = $matches[1];
            }
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_group_name'));
        }

        //valid status
        $validStatus = new Valid('group_status_search');
        $validStatus->addRule(new Rule_WhiteList($statusWhiteList));

        if ($request->valid($validStatus)) {
            $this->status = $request->get('group_status_search');                
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_status'));
        }

        //valid state
        $validState = new Valid('group_state_search');
        $validState->addRule(new Rule_WhiteList($stateWhiteList));

        if ($request->valid($validState)) {
            $this->state = $request->get('group_state_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_state'));
        }

        //valid type
        $validType = new Valid('group_type_search');
        $validType->addRule(new Rule_WhiteList($typeWhiteList));

        if ($request->valid($validType)) {
            $this->type = $request->get('group_type_search');
        }
        else  {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('admin_group_controler','wrong_type'));
        }

        if ($this->shortcut != '') {
                $filter[] = new GroupShortcutFilter($this->shortcut);                
        }
        if ($this->name != '') {
            $filter[] = new GroupNameFilter($this->name);
        }
        if ($this->status != '' && $this->status != 'all') {
            $filter[] = new GroupStatusFilter($this->status);
        }
        if ($this->state != '' && $this->state != 'any') {
            $filter[] = new GroupStateFilter($this->state);
        }
        if ($this->type != '' && $this->type != 'any') {
            $filter[] = new GroupTypeFilter($this->type);
        }
        $this->adminEmailIterator = $dao->searchAdminEmailByFilter($filter);        
    }

    /**
     * request()
     */
    function request() {

        $this->setOffset();

        $this->setLimit();

        $this->setAdminEmailIterator();

        $this->setMainGroupIterator();

        $this->setNbGroup();
    }
}

?>
