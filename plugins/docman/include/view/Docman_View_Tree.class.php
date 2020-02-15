<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_TreeView
*/

require_once('Docman_View_Browse.class.php');
require_once('Docman_View_RawTree.class.php');

class Docman_View_Tree extends Docman_View_Browse
{

    /* protected */ public function _content($params)
    {
        $v = new Docman_View_RawTree($this->_controller);
        $v->_content($params);
        $this->javascript .= $v->javascript;
    }
}
