<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_PositionWithinFolder
*/

require_once('Docman_View_View.class.php');

class Docman_View_PositionWithinFolder extends Docman_View_View  /* implements Visitor*/
{

    /* protected */ function _content($params)
    {
        echo '<select name="ordering">';
        echo '<option value="beginning" '. ($params['force_ordering'] === 'beginning' ? 'selected="selected"' : '') .'>'. $GLOBALS['Language']->getText('plugin_docman', 'move_position_beginning') .'</option>';
        echo '<option value="end"'. ($params['force_ordering'] === 'end' ? 'selected="selected"' : '') .'>'. $GLOBALS['Language']->getText('plugin_docman', 'move_position_end') .'</option>';
        $params['hierarchy']->accept($this, array(
            'parent_id'      => $params['item']->getId(),
            'force_ordering' => $params['force_ordering'],
            'exclude'         => $params['exclude']
        ));

        echo '</select>';
    }
    function _displayItem($item, $params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        if (!$params['exclude'] || $params['exclude'] != $item->getId()) {
            echo '<option value="'. ($item->getRank()+1) .'" '. ($params['force_ordering'] === ("".($item->getRank()+1)) ? 'selected="selected"' : '') .'>After '.  $hp->purify($item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML)  .'</option>';
        }
    }
    function visitFolder(&$item, $params = array())
    {
        if ($item->getParentId() == $params['parent_id']) {
            $this->_displayItem($item, $params);
        } else {
            $items = $item->getAllItems();
            $it = $items->iterator();
            while ($it->valid()) {
                $o = $it->current();
                $r = $o->accept($this, $params);
                $it->next();
            }
        }
    }

    function visitDocument(&$item, $params = array())
    {
        if ($item->getParentId() == $params['parent_id']) {
            $this->_displayItem($item, $params);
        }
    }
    function visitWiki(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    function visitEmpty(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
}
