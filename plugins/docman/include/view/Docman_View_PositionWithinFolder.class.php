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

    /* protected */ public function _content($params)
    {
        echo '<select name="ordering">';
        echo '<option value="beginning" ' . ($params['force_ordering'] === 'beginning' ? 'selected="selected"' : '') . '>' . dgettext('tuleap-docman', 'At the beginning') . '</option>';
        echo '<option value="end"' . ($params['force_ordering'] === 'end' ? 'selected="selected"' : '') . '>' . dgettext('tuleap-docman', 'At the end') . '</option>';
        $params['hierarchy']->accept($this, array(
            'parent_id'      => $params['item']->getId(),
            'force_ordering' => $params['force_ordering'],
            'exclude'         => $params['exclude']
        ));

        echo '</select>';
    }
    public function _displayItem($item, $params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        if (!$params['exclude'] || $params['exclude'] != $item->getId()) {
            echo '<option value="' . ($item->getRank() + 1) . '" ' . ($params['force_ordering'] === ("" . ($item->getRank() + 1)) ? 'selected="selected"' : '') . '>After ' .  $hp->purify($item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML)  . '</option>';
        }
    }
    public function visitFolder(&$item, $params = array())
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

    public function visitDocument(&$item, $params = array())
    {
        if ($item->getParentId() == $params['parent_id']) {
            $this->_displayItem($item, $params);
        }
    }
    public function visitWiki(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitLink(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
    public function visitEmbeddedFile(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmpty(&$item, $params = array())
    {
        return $this->visitDocument($item, $params);
    }
}
