<?php

/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * 
 * $Id$
 *
 * DataGeneratorViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');

class DataGeneratorViews extends Views {
    
    function DataGeneratorViews(&$controler, $view=null) {
        $this->View($controler, $view);
    }
    
    function header() {
        $title = $GLOBALS['Language']->getText('plugin_datagenerator','title');
        $GLOBALS['HTML']->header(array('title'=>$title));
        echo '<h2>'.$title.'</h2>';
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function index() {
        echo '<form action="/plugins/datagenerator/?action=generate" method="POST">';
        
        echo '<fieldset>';
        echo '<legend><input type="hidden" name="data[users][generate]" value="0" /><input type="checkbox" name="data[users][generate]" id="data_users" value="1" /><label for="data_users">Users</label></legend>';
        echo '<table>';
        echo '<tr><td>Nb to create:</td><td><input type="text" name="data[users][nb]" value="10" /></td></tr>';
        echo '</table>';
        echo '</fieldset>';
        
        echo '<fieldset>';
        echo '<legend><input type="hidden" name="data[projects][generate]" value="0" /><input type="checkbox" name="data[projects][generate]" id="data_projects" value="1" /><label for="data_projects">Projects</label></legend>';
        echo '<table>';
        echo '<tr><td>Nb to create:</td><td><input type="text" name="data[projects][nb]" value="10" /></td></tr>';
        echo '</table>';
        echo '</fieldset>';
        
        echo '<input type="submit" value="Generate!" />';
        echo '</form>';
    }
    // }}}
}


?>