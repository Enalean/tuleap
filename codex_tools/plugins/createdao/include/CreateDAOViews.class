<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * CreateDAOViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/dao/DBTablesDao.class.php');
require_once('common/dao/CodexDataAccess.class.php');

class CreateDAOViews extends Views {
    
    function CreateDAOViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $GLOBALS['Language']->loadLanguageMsg('createdao', 'createdao');
    }
    
    function header() {
        $title = $GLOBALS['Language']->getText('plugin_createdao','title');
        $GLOBALS['HTML']->header(array('title'=>$title));
        echo '<h2>'.$title.'</h2>';
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function browse() {
        $request =& HTTPRequest::instance();
        $path_to_dao = $request->exist('path_to_dao')?$request->get('path_to_dao'):$this->_getPathToDao();
        $da =& CodexDataAccess::instance();
        $tables   =& new DBTablesDao($da);
        $result   =& $tables->searchAll();
        $missings = array();
        while ($result->valid()) {
            $row = $result->current();
            $name = $row['Tables_in_'.$GLOBALS['sys_dbname']]."\n";
            $name = ucfirst($name);
            while ($pos = strpos($name, "_")) {
                $name = substr($name, 0, $pos).ucfirst(substr($name, $pos+1));
            }
            $name = substr($name, 0, strlen($name)-1);
            if (!is_file($path_to_dao.$name.'Dao.class.php')) {
                $missings[$row['Tables_in_sourceforge']] = $name;
            }
            $result->next();
        }
        echo '<form action="?" method="POST"><div><label for="path_to_dao" style="font-weight:bold;">Path to DAO: </label><input type="text" size="'.strlen($path_to_dao).'" name="path_to_dao" value="'.$path_to_dao.'" /><table>';
        foreach ($missings as $key => $missing) {
            echo '<tr><td>'.$missing."</td><td><button name='name' value='".$key."'>Generate now !</button></td></tr>";
        }
        echo '</table><input type="hidden" name="action" value="create" /></div></form>';
    }
    // }}}
    
    function _getPathToDao() {
        $ctrl =& $this->getControler();
        $plug =& $ctrl->getPlugin();
        $info =& $plug->getPluginInfo();
        return $info->getPropertyValueForName('path_to_dao');
    }
}


?>