<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

class Docman_View_ParentsTree /* implements Visitor*/
{
    public $docman;
    public function __construct(&$docman)
    {
        $this->docman = $docman;
    }


    //docman_icons
    //current
    //hierarchy
    public function fetch($params)
    {
        $html  = '';
        $html .= '<div id="docman_new_item_location_current_folder"></div>';
        $html .= '<div id="docman_new_item_location_other_folders">';
        $html .= '<ul class="docman_new_parentfolder docman_items">';
        $html .= $this->fetchFolder($params['hierarchy']->accept($this, $params), array(
            'is_last'    => true,
            'select'     => $params['current'],
            'input_name' => isset($params['input_name']) ? $params['input_name'] : 'item[parent_id]'
        ));
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '<div id="docman_new_item_location_position_panel" style="border-top:1px solid #e7e7e7">Position : ';
        $html .= '<span id="docman_new_item_location_position">';
        $html .= '<select id="docman_item_ordering" name="ordering">';
        $html .= '<option value="beginning">' . dgettext('tuleap-docman', 'At the beginning') . '</option>';
        $html .= '<option value="end">' . dgettext('tuleap-docman', 'At the end') . '</option>';
        $html .= '</select>';
        $html .= '</span>';
        $html .= '</div>';
        return $html;
    }
    public function fetchFolder($folder, $params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $selected = '';
        if (!isset($params['selected']) || !$params['selected']) {
            if ($this->docman->userCanWrite($folder['id']) && (!$params['select'] || $params['select'] == $folder['id'])) {
                $selected = 'checked="checked"';
                $params['selected'] = true;
            }
        }
        $disabled = ($this->docman->userCanWrite($folder['id'])) ? '' : 'disabled="disabled"';
        $label_classes = $selected ? 'docman_item_actual_parent' : '';

        $h  = '<li  class="' . Docman_View_Browse::getItemClasses(array('is_last' => $params['is_last'])) . '">';
        $h .= '<label for="item_parent_id_' . $folder['id'] . '" class="' . $label_classes . '" >';
        $h .= '<input type="radio" ' . $selected . ' name="' . $params['input_name'] . '" value="' . $folder['id'] . '" id="item_parent_id_' . $folder['id'] . '" ' . $disabled . ' />';
        $h .= '<img src="' . $folder['icon_src'] . '" class="docman_item_icon" />';
        $h .=  $hp->purify($folder['title'], CODENDI_PURIFIER_CONVERT_HTML)  . '</label>';
        $h .= '<script type="text/javascript">docman.addParentFoldersForNewItem(' . $folder['id'] . ', ' . $folder['parent_id'] . ", '" .  $hp->purify(addslashes($folder['title']), CODENDI_PURIFIER_CONVERT_HTML) . "');</script>\n";
        $h .= '<ul class="docman_items">';

        $params['is_last'] = false;
        $nb = count($folder['items']);
        $i = 0;
        foreach ($folder['items'] as $item) {
            $i++;
            if ($i == $nb) {
                $params['is_last'] = true;
            }
            $h .= $this->fetchFolder($item, $params);
        }
        return $h . '</ul></li>';
    }

    public function _itemCanBeFetched(&$item, $params)
    {
        $ok = !isset($params['excludes']) || !in_array($item->getId(), $params['excludes']);
        return $ok;
    }

    public function visitFolder(&$item, $params = array())
    {
        $t = '';
        if ($this->docman->userCanRead($item->getId()) && $this->_itemCanBeFetched($item, $params)) {
            $t = array(
                'id'        => $item->getId(),
                'parent_id' => $item->getParentId(),
                'title'     => $item->getTitle(),
                'items'     => array(),
                'icon_src'  => $params['docman_icons']->getIconForItem($item, array('expanded' => true))
            );

            $items = $item->getAllItems();
            $it = $items->iterator();
            while ($it->valid()) {
                $o = $it->current();
                if ($this->_itemCanBeFetched($o, $params)) {
                    $r = $o->accept($this, $params);
                    if ($r) {
                        $t['items'][] = $r;
                    }
                }
                $it->next();
            }
        }
        return $t;
    }
    public function visitDocument(&$item, $params = array())
    {
        return false;
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
