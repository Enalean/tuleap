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
require_once('account.php');
require_once('www/project/admin/ugroup_utils.php');
require_once('common/admin/view/AdminEditDisplay.class.php');

/**
 * UserChangeNameDisplay
 *
 */
class UserChangeNameDisplay extends AdminEditDisplay { //ou est-ce que ca doit etendre UserEditDisplay


    function __construct($userparam, $groupparam, $task) {
        $this->userparam = $userparam;
        $this->groupparam = $groupparam;
        $this->task = $task;
        
    }

    /**
     * displayHeader()
     *
     */
    function displayHeader() {
        $GLOBALS['Language']->loadLanguageMsg('admin/admin');
      
        $GLOBALS['HTML']->includeJavascriptFile("/scripts/calendar_js.php");
        session_require(array('group'=>'1','admin_flags'=>'A'));
        
        $GLOBALS['HTML']->header(array('title'=>$GLOBALS['Language']->getText('admin_usergroup','title')));

        if(isset($this->userparam['user_id'])) {
  parent::displayHeader('<h2>'.$GLOBALS['Language']->getText('admin_usergroup','header').': '.$this->userparam['user_name'].' (ID '.$this->userparam['user_id'].')</h2>');
        }
        elseif(count($this->userparam) == 1 ) {
            parent::displayHeader('<h2>'.$GLOBALS['Language']->getText('admin_usergroup','header').': '.$this->userparam[0]['user_name'].' (ID '.$this->userparam[0]['user_id'].')</h2>');
        }
        ?>
            <script type="text/javascript" src="/scripts/prototype/prototype.js"></script>           
                 <script type="text/javascript" src="/scripts/autoselectlist.js"></script>
                 
                 <?php
                 }


    /**
     * displayForm()
     *
     */
    function displayForm() {

        print '<p>New Codex Name : <input type="text" name="new_codex_name" id="new_codex_name" /></p>';

        print '<p><input type="submit" name="submit" value="Submit" /></p>';

    }



    /**
     * displayFooter()
     *
     */
    function displayFooter() {
        $GLOBALS['HTML']->footer(array());
    }
    


    /**
     * display()
     *
     */
    function display() {

        $this->displayHeader();
        $this->displayForm();
        $this->displayFooter();
   
    }


}



?>