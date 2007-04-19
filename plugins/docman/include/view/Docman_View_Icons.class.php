<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_Icons
*/

require_once('Docman_View_Browse.class.php');
require_once('Docman_View_RawTree.class.php');
require_once('Docman_View_GetMenuItemsVisitor.class.php');

class Docman_View_Icons extends Docman_View_Browse {
    
    /* protected */ function _content($params) {
        
        $html = '';
        
        $itemBo = new Docman_ItemBo($params['group_id']);
        $itemTree =& $itemBo->getItemSubTree($params['item']->getId(), array('user' => $params['user']));
        
        $items = $itemTree->getAllItems();
        $nb = $items->size();
        if ($nb) { 
            $html .= '<table border="0" cellpadding="0" cellspacing="4" width="100%">';
            $folders   = array();
            $documents = array();
            $it =& $items->iterator();
            while($it->valid()) {
                $o =& $it->current();
                $this->is_folder = false;
                $o->accept($this);
                if ($this->is_folder) {
                    $folders[] =& $o;
                } else {
                    $documents[] =& $o;
                }
                $it->next();
            }
            $nb_of_columns = 4;
            $width = floor(100 / $nb_of_columns);
            $sort = create_function('&$a, &$b', 'return strnatcasecmp($a->getTitle(), $b->getTitle());');
            usort($folders, $sort);
            usort($documents, $sort);
            $cells = array_merge($folders, $documents);
            $rows = array_chunk($cells, $nb_of_columns);
            $item_parameters = array(
                'icon_width'            => '32',
                'theme_path'            => $params['theme_path'],
                'get_action_on_icon'    => new Docman_View_GetActionOnIconVisitor(),
                'docman_icons'           => $this->_getDocmanIcons($params),
                'default_url'            => $params['default_url'],
                //'display_description'    => isset($params['display_description']) ? $params['display_description'] : true,
                'show_options'           => ($this->_controller->request->exist('show_options') ? $this->_controller->request->get('show_options') : false),
                'item'                  => $params['item'],
            );
            foreach($rows as $row) {
                $html .= '<tr style="vertical-align:top">';
                foreach($row as $cell => $nop) {
                    $html .= '<td width="'. $width .'%">'. $this->_displayItem($row[$cell], $item_parameters) .'</td>';
                }
                $html .= '<td width="'. $width .'%">&nbsp;</td>';
                $html .= '</tr>';
            }
            $html .= '</table>'."\n";
        }
        echo $html;
    }
    
    function visitFolder(&$item, $params) {
        $this->is_folder = true;
    }
    function visitDocument(&$item, $params) {
    }
    function visitWiki(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }

    function visitEmpty(&$item, $params = array()) {
        return $this->visitDocument($item, $params);
    }
    
    function _displayItem(&$item, $params) {
        $html = '<div id="item_'.$item->getId().'" class="'. Docman_View_Browse::getItemClasses($params) .'" style="position:relative;">';
        
        $show_options = isset($params['show_options']) && $params['show_options'] == $item->getId();
        
        $icon_src = $params['docman_icons']->getIconForItem($item, $params);
        $icon = '<img src="'. $icon_src .'" class="docman_item_icon" style="vertical-align:middle; text-decoration:none;" />';
        
        $icon_url = $this->buildUrl($params['default_url'], array(
            'action' => $item->accept($params['get_action_on_icon'], array('view' => $this)),
            'id'     => $item->getId()
        ));
        $title_url = $this->buildUrl($params['default_url'], array(
            'action' => 'show',
            'id'     => $item->getId()
        ));
        $html .= '<div><a href="'. $icon_url .'">'. $icon .'</a>';
        $html .= '<span class="docman_item_title"><a href="'. $title_url .'">'. $item->getTitle() .'</a></span>';
        $html .= '</a>';
        //Show/hide options {{{
        $html .= $this->getItemMenu($item, $params);
        //}}}
        if (trim($item->getDescription()) != '') {
            $html .= '<div class="docman_item_description">'. $item->getDescription() .'</div>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }
}

?>
