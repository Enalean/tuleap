<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_TreeView
*/

require_once('Docman_View_Browse.class.php');
require_once('Docman_View_RawTree.class.php');

class Docman_View_Tree extends Docman_View_Browse {
    
    /* protected */ function _content($params) {
       $v =& new Docman_View_RawTree($this->_controller);
       $v->_content($params);
    }
}

?>
