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
     * $groupIterator
     *
     * @type Iterator $groupIterator
     */
    private $groupIterator;
    
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
     * @type int $nbuser
     */
    private $nbuser;

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
        $groupSearchDisplay = new GroupSearchDisplay($this->groupIterator,$this->offset,$this->limit, $this->nbuser);
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
     * setGroupIterator()
     */
    function setGroupIterator() {

        $dao = new GroupDao(CodexDataAccess::instance());
        
        $criteria = array();

        $request =& HTTPRequest::instance();



        //ecrire le code des tests...

        
        $this->userIterator = $dao->searchUserByCriteria($criteria, $this->getOffset(), $this->getLimit());    
        // }
        // else {
        //      $this->userIterator = $dao->searchAll($this->getOffset(), $this->getLimit());         
        // }
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
        return $this->nbuser;
    }
    
    /**
     * request()
     */
    function request() {
        
        $this->setOffset($_GET['offset']);
        
        $this->setLimit();
        
        $this->setGroupIterator();
        
        $this->setNbGroup();
        
    }
    


}







// $Language->loadLanguageMsg('admin/admin');

// session_require(array('group'=>'1','admin_flas'=>'A'));

// $HTML->header(array('title'=>$Language->getText('admin_userlist','title')));


// $groupSearchDisplay = new GroupSearchDisplay();


// //Header
// $groupSearchDisplay->displayHeader();

// //Search
// $groupSearchDisplay->displaySearchFilter();

// //Browsing
// $groupSearchDisplay->displayBrowse();


// //Search table
// $groupSearchDisplay->displaySearch();


// //Browsing
// $groupSearchDisplay->displayBrowse();

// $HTML->footer(array());

?>
