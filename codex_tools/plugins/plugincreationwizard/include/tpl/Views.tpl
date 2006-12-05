
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 * 
 * $Id$
 *
 * <?=$class_name?>Views
 */
require_once('common/mvc/Views.class');
require_once('common/include/HTTPRequest.class');

class <?=$class_name?>Views extends Views {
    
    function <?=$class_name?>Views(&$controler, $view=null) {
        $this->View($controler, $view);
        $GLOBALS['Language']->loadLanguageMsg('<?=$short_name?>', '<?=$short_name?>');
    }
    
    function header() {
        $title = $GLOBALS['Language']->getText('plugin_<?=$short_name?>','title');
        $GLOBALS['HTML']->header(array('title'=>$title));
        echo '<h2>'.$title.'</h2>';
    }
    function footer() {
        $GLOBALS['HTML']->footer(array());
    }
    
    // {{{ Views
    function hello() {
        echo 'Hello !';
    }
    // }}}
}


