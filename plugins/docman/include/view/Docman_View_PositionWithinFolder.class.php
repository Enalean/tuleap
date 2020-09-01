<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
*
*
* Docman_View_PositionWithinFolder
*/

class Docman_View_PositionWithinFolder extends Docman_View_View  /* implements Visitor*/
{

    /* protected */ public function _content($params)
    {
        echo '<select name="ordering">';
        echo '<option value="beginning" ' . ($params['force_ordering'] === 'beginning' ? 'selected="selected"' : '') . '>' . dgettext('tuleap-docman', 'At the beginning') . '</option>';
        echo '<option value="end"' . ($params['force_ordering'] === 'end' ? 'selected="selected"' : '') . '>' . dgettext('tuleap-docman', 'At the end') . '</option>';
        $params['hierarchy']->accept($this, [
            'parent_id'      => $params['item']->getId(),
            'force_ordering' => $params['force_ordering'],
            'exclude'         => $params['exclude']
        ]);

        echo '</select>';
    }
    public function _displayItem($item, $params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        if (! $params['exclude'] || $params['exclude'] != $item->getId()) {
            echo '<option value="' . ($item->getRank() + 1) . '" ' . ($params['force_ordering'] === ("" . ($item->getRank() + 1)) ? 'selected="selected"' : '') . '>After ' .  $hp->purify($item->getTitle(), CODENDI_PURIFIER_CONVERT_HTML)  . '</option>';
        }
    }
    public function visitFolder(&$item, $params = [])
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

    public function visitDocument(&$item, $params = [])
    {
        if ($item->getParentId() == $params['parent_id']) {
            $this->_displayItem($item, $params);
        }
    }
    public function visitWiki(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitLink(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitFile(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
    public function visitEmbeddedFile(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }

    public function visitEmpty(&$item, $params = [])
    {
        return $this->visitDocument($item, $params);
    }
}
