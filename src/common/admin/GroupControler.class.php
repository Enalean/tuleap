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
     * constructor
     *
     */    
    function __construct() {

    }

    /**
     * viewManagement()
     */
    function viewsManagement() {        
        $groupSearchDisplay = new GroupSearchDisplay($this->groupArray,$this->offset,$this->limit, $this->nbgroup);
        $groupSearchDisplay->display();       
    }

    /**
     * setNbUser()
     */
    function setNbGroup() {
        $dao = new GroupDao(CodexDataAccess::instance());

        echo '<pre>';
        var_dump($dao);
        echo '</pre>';
        $this->nbgroup = $dao->getFoundRows();
    }

    /**
     * setOffset()
     *
     * @param int $offset
     */
    function setOffset($offset = null) {

        if ($offset === null) {
            $this->offset = 0;
        }
        else {
            $request =& HTTPRequest::instance();
            
            $voffset = new valid('offset');
            $voffset->addRule(new Rule_Int());
            
            if ($request->valid($voffset)) {
                $offset = $request->get('offset');
                $this->offset = $offset;
            }
        }
    }

    /**
     * setLimit()
     */
    function setLimit() {
        
        $request =& HTTPRequest::instance();
        
        if ($_GET['limit']) {

            $vlimit = new Valid('limit');
            $vlimit->addRule(new Rule_Int());

            if ($request->valid($vlimit)) {
                $limit = $request->get('limit');
                $this->limit = $limit;
            }
            else {

                $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
            }
        }
        elseif ($_POST['nbtodisplay']) {

            $v = new Valid('nbtodisplay');
            $v->addRule(new Rule_Int());

            if ($request->valid($v)) {
                if ($request->isPost()) {
                    $limit = $request->get('nbtodisplay');
                    $this->limit = $limit;
                }
                else {
                    $GLOBALS['Response']->addFeedback('error', 'Your data don\'t provide to POST'); 
                }
            }
            else {
                $GLOBALS['Response']->addFeedback('error', 'Your data are not valid'); 
            }
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

        //valid parameters

        //valid shortcut
        $validShortcut = new Valid('group_shortcut_search');
       
        $validShortcut->addRule(new Rule_WhiteList($shortcutWhiteList));
                
        if($request->valid($validShortcut)) {
            $shortcut = $request->get('group_shortcut_search');
        }
        else {
            $GLOBALS['Response']->addFeedback('error', 'Your data are not valid');
        }


        if (isset($shortcut)) {
                $filter[] = new GroupShortcutFilter($shortcut);
                
        }

        $this->mainGroupIterator = $dao->searchGroupByFilter($filter, $this->getOffset(), $this->getLimit());
    }

    /**
     * setAdminEmailIterator
     */
    function setAdminEmailIterator() {

        $dao = new GroupDao(CodexDataAccess::instance());

        $filter = array();

        $this->adminEmailIterator = $dao->searchAdminEmail();
        
    }

    /**
     * mergeGroupIterators()
     */
    function mergeGroupIterators () {

        var_dump($this->mainGroupIterator);

        $this->groupArray[] = array();

        foreach ($this->mainGroupIterator as  $mgi) {

            foreach ($this->adminEmailIterator as $keyaei => $valaei) {

                if ($mgi['group_id'] == $valaei['group_id']) {

                    $this->groupArray[$keyaei]['group_id'] .= $mgi['group_id'];
                    $this->groupArray[$keyaei]['group_name'] .= $mgi['group_name'];
                    $this->groupArray[$keyaei]['unix_group_name'] .= $mgi['unix_group_name'];
                    $this->groupArray[$keyaei]['status'] .= $mgi['status'];
                    $this->groupArray[$keyaei]['type'] .= $mgi['type'];
                    $this->groupArray[$keyaei]['name'] .= $mgi['name'];
                    $this->groupArray[$keyaei]['is_public'] .= $mgi['is_public'];
                    $this->groupArray[$keyaei]['license'] .= $mgi['license'];
                    $this->groupArray[$keyaei]['c'] .= $mgi['c'];
                    
                    $this->groupArray[$keyaei]['email'] .= $valaei['email'];

                    if (count($valaei['email'])>1) {
                        $i = 1;

                        while($i < count($valaei['email'])) {
                            $this->groupArray[$keyaei]['email'] .= ';'.$valaei['email'];
                            $i++;
                        }
                    }
                    $this->groupArray[$keyaei]['user_id'] .= $valaei['user_id'];
                }
            }
        }
    }

    /**
     * getOffset()
     */    
    function getOffset() {
        return $this->offset;
    }
    
    /**
     * getLimit()
     */
    function getLimit() {
        return $this->limit;
    }
    
    /**
     * getNbUser()
     */
    function getNbGroup() {
        return $this->nbgroup;
    }
    
    /**
     * request()
     */
    function request() {
        
        $this->setOffset($_GET['offset']);
        
        $this->setLimit();
        
        $this->setMainGroupIterator();
         
        $this->setAdminEmailIterator();

        $this->setNbGroup();   

        $this->mergeGroupIterators();
        
        //$this->setNbGroup();        
    }
}

?>
