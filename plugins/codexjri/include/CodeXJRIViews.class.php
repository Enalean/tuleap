<?php

/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * 
 *
 * CodeXJRIViews
 */
require_once('common/mvc/Views.class.php');
require_once('common/include/HTTPRequest.class.php');

class CodeXJRIViews extends Views {
    
    function CodeXJRIViews(&$controler, $view=null) {
        $this->View($controler, $view);
        $GLOBALS['Language']->loadLanguageMsg('codexjri', 'codexjri');
    }
    
    function header() {
        $title = $GLOBALS['Language']->getText('plugin_codexjri','title');
        $GLOBALS['HTML']->header(array('title'=>$title));
        echo '<h2>'.$title.'</h2>';
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function index() {
        $this->intro();
        $this->documentation();
        $this->sources();
    }
    // }}}
    
    function intro() {
        echo file_get_contents($GLOBALS['Language']->getContent('intro', null, 'codexjri'));
    }
    
    function documentation() {
        echo file_get_contents($GLOBALS['Language']->getContent('documentation', null, 'codexjri'));
    }
    
    function sources() {
        echo file_get_contents($GLOBALS['Language']->getContent('sources', null, 'codexjri'));
    }

}


?>