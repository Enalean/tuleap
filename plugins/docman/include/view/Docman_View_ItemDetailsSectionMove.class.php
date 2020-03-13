<?php
/**
 * Copyright © Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 20062006
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

require_once('Docman_View_ItemDetailsSectionActions.class.php');
require_once('Docman_View_ParentsTree.class.php');

class Docman_View_ItemDetailsSectionMove extends Docman_View_ItemDetailsSectionActions
{

    public $token;
    public function __construct($item, $url, $controller, $params, $token)
    {
        parent::__construct($item, $url, false, true, $controller);
        $this->params = $params;
        $this->token = $token;
    }
    public function getContent($params = [])
    {
        $content = '';
        $content .= '<dl><dt>' . dgettext('tuleap-docman', 'Move') . '</dt><dd>';
        $content .= '<form action="' . $this->url . '" method="POST">';

        $parents_tree = new Docman_View_ParentsTree($this->_controller);
        $content .= $parents_tree->fetch(array(
            'docman_icons' => $this->params['docman_icons'],
            'current'      => $this->item->getParentId(),
            'hierarchy'    => $this->params['hierarchy'],
            'input_name'   => 'id',
            'excludes'     => array($this->item->getId())
        ));
        $content .= '<script type="text/javascript">docman.options.move.item_id = ' . $this->item->getId() . ';</script>';
        $content .=  '<br />';

        //submit
        $content .= '<div>';
        if ($this->token) {
            $content .= '<input type="hidden" name="token" value="' . $this->token . '" />';
        }
        $content .= '<input type="hidden" name="action" value="move_here" />';
        $content .= '<input type="hidden" name="item_to_move" value="' . $this->item->getId() . '" />';
        $content .= '<input type="submit" tabindex="2" name="confirm" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';
        $content .= '<input type="submit" tabindex="1" name="cancel" value="' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '" />';
        $content .= '</div></form>';
        $content .= '</dd></dl>';
        return $content;
    }
    /* protected */ public function _getJSDocmanParameters()
    {
        return array('action' => 'move');
    }
}
