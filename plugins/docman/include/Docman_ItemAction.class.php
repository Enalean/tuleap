<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

use Tuleap\Docman\View\DocmanViewURLBuilder;

/**
 * ItemAction is a transport object (aka container) used to share data between
 * Model/Controler and View layer of the application
 */
class Docman_ItemAction
{
    var $item;
    var $action;
    var $class;
    var $title;
    var $other_icons;
    var $extraUrlParams;

    function __construct(&$item)
    {
        $this->item             = $item;
        $this->action           = '';
        $this->classes          = '';
        $this->title            = '';
        $this->other_icons      = array();
        $this->extraUrlParams   = array();
    }

    function fetchAction($params)
    {
        $url   = $params['default_url'].'&action='.$this->action.'&id='.$this->item->getId();
        $title = $this->title;
        $href  = '<a href="'.$url.'">'.$title.'</a>';
        $html  = '<li>'.$href.'</li>';
        return $html;
    }

    function fetch($params)
    {
        $dfltUrlParams = array('action' => $this->action,
                               'id' => $this->item->getId());
        $_urlParams = array_merge($dfltUrlParams, $this->extraUrlParams);
        $url = DocmanViewURLBuilder::buildActionUrl(
            $this->item,
            $params,
            $_urlParams,
            true,
            true
        );
        $html  = '<a href="'.$url.'" class="'. $this->classes .'" title="'. $this->title .'">';
        $html .= '<img src="'. $params['docman_icons']->getActionIcon($this->action) .'" class="docman_item_icon" alt="['. $this->title .']" />';
        $html .= '</a>&nbsp;';
        return $html;
    }
}
class Docman_ItemActionNewFolder extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'newFolder';
        $this->classes          = 'docman_item_option_newfolder';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_newfolder');
    }
}
class Docman_ItemActionNewDocument extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'newDocument';
        $this->classes          = 'docman_item_option_newdocument';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_newdocument');
    }
}
class Docman_ItemActionDetails extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details';
        $this->classes          = 'docman_item_option_details';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_details');
    }
}
class Docman_ItemActionNewVersion extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'action_new_version';
        $this->classes          = 'docman_item_option_newversion';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_newversion');
    }
}
class Docman_ItemActionMove extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'move';
        $this->classes          = 'docman_item_option_move';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_move');
        $this->other_icons[]    = 'move-up';
        $this->other_icons[]    = 'move-down';
        $this->other_icons[]    = 'move-beginning';
        $this->other_icons[]    = 'move-end';
    }
}
class Docman_ItemActionPermissions extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details&section=permissions';
        $this->classes          = 'docman_item_option_permissions';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_permissions');
    }
}
class Docman_ItemActionHistory extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details&section=history';
        $this->classes          = 'docman_item_option_history';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_history');
    }
}
class Docman_ItemActionNotifications extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details&section=notifications';
        $this->classes          = 'docman_item_option_notifications';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_notifications');
    }
}
class Docman_ItemActionDelete extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'confirmDelete';
        $this->classes          = 'docman_item_option_delete';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_delete');
    }
}
class Docman_ItemActionUpdate extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'action_update';
        $this->classes          = 'docman_item_option_update';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_update');
    }
}

class Docman_ItemActionCopy extends Docman_ItemAction
{
    function __construct(&$item, $params)
    {
        parent::__construct($item);
        $this->action           = 'action_copy';
        $this->classes          = 'docman_item_option_copy';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_copy');

        $currentItem = $params['item'];
        $origAction = isset($params['action']) ? $params['action'] : 'show';
        $this->extraUrlParams   = array('orig_id'     => $currentItem->getId(),
                                        'orig_action' => $origAction);
    }
}

class Docman_ItemActionCut extends Docman_ItemAction
{
    function __construct(&$item, $params)
    {
        parent::__construct($item);
        $this->action           = 'action_cut';
        $this->classes          = 'docman_item_option_cut';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_cut');

        $currentItem = $params['item'];
        $origAction = isset($params['action']) ? $params['action'] : 'show';
        $this->extraUrlParams   = array('orig_id'     => $currentItem->getId(),
                                        'orig_action' => $origAction);
    }
}

class Docman_ItemActionPaste extends Docman_ItemAction
{
    function __construct(&$item, $params)
    {
        parent::__construct($item);
        $this->action           = 'action_paste';
        $this->classes          = 'docman_item_option_paste';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_paste');
    }
}

class Docman_ItemActionApproval extends Docman_ItemAction
{
    function __construct(&$item)
    {
        parent::__construct($item);
        $this->action           = 'details&section=approval';
        $this->classes          = 'docman_item_option_approval';
        $this->title            = $GLOBALS['Language']->getText('plugin_docman', 'action_approval');
    }
}
