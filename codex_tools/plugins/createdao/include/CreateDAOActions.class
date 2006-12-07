<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * CreateDAOActions
 */
require_once('common/mvc/Actions.class.php');
require_once('common/include/HTTPRequest.class.php');
require_once('common/dao/DBTablesDao.class.php');
require_once('common/dao/CodexDataAccess.class.php');

class CreateDAOActions extends Actions {
    
    function  CreateDAOActions(&$controler, $view=null) {
        $this->Actions($controler);
    }
    // {{{ Actions
    function create() {
        $request =& HTTPRequest::instance();
        $path_to_dao = $request->exist('path_to_dao')?$request->get('path_to_dao'):$this->_getPathToDao();
        var_dump($path_to_dao);
        $da =& CodexDataAccess::instance();
        $tables   =& new DBTablesDao($da);
        $result   =& $tables->searchAll();
        while ($result->valid()) {
            $row = $result->current();
            $name = $row['Tables_in_sourceforge']."\n";
            $name = ucfirst($name);
            while ($pos = strpos($name, "_")) {
                $name = substr($name, 0, $pos).ucfirst(substr($name, $pos+1));
            }
            $name = substr($name, 0, strlen($name)-1);
            if (!is_file($path_to_dao.$name.'Dao.class.php')) {
                if (isset($_REQUEST['name']) && $_REQUEST['name'] === $row['Tables_in_'.$GLOBALS['sys_dbname']]) {
                    require_once('Template.class.php');
                    $tpl =& new Template();
                    $tpl->set('classname', $name);
                    $tpl->set('table', $row['Tables_in_'.$GLOBALS['sys_dbname']]);
                    
                    $fields    =& $tables->searchByName($_REQUEST['name']);
                    $all_fields = array();
                    while($row = $fields->getRow()) {
                        $all_fields[$row['Field']] = $row['Field'];
                    }
                    
                    $create_fields  = $all_fields;
                    $auto_increment = false;
                    
                    $fields    =& $tables->searchByName($_REQUEST['name']);
                    $accessors = '';
                    while($row = $fields->getRow()) {
                        $other_fields = $all_fields;
                        if (preg_match('/auto_increment/', $row['Extra']) > 0) {
                            unset($create_fields[$row['Field']]);
                            $auto_increment = true;
                        }
                        unset($other_fields[$row['Field']]);
                        $other_fields = implode(', ', $other_fields);
                        
                        $tpl_access =& new Template();
                        $tpl_access->set('field', $row['Field']);
                        $tpl_access->set('other_fields', $other_fields);
                        $tpl_access->set('classname', $name);
                        $tpl_access->set('table', $_REQUEST['name']);
                        $field_name = ucfirst($row['Field']);
                        while ($pos = strpos($field_name, "_")) {
                            $field_name = substr($field_name, 0, $pos).ucfirst(substr($field_name, $pos+1));
                        }
                        $tpl_access->set('fieldname' , $field_name);
                        $accessors .= $tpl_access->fetch('tpl/accessor.tpl');
                    }
                    $tpl_create =& new Template();
                    $tpl_create->set('table', $_REQUEST['name']);
                    $tpl_create->set('create_fields', $create_fields);
                    $tpl_create->set('auto_increment', $auto_increment);
                    $accessors .= $tpl_create->fetch('tpl/create.tpl');
                    
                    $tpl->set('accessors', $accessors);
                    if ($f = fopen($path_to_dao.$name.'Dao.class.php', "w")) {
                        fwrite($f, "<");
                        fwrite($f, "?php\n");
                        fwrite($f, $tpl->fetch('tpl/dao.tpl'));
                        fwrite($f, "\n?".">");
                        fclose($f);
                    } else {
                        echo "<pre>".$tpl->fetch('tpl/dao.tpl');
                        $missings[$row['Tables_in_sourceforge']] = $name;
                    }
                }
            }
            $result->next();
        }
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